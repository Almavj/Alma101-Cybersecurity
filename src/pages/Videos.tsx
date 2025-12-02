import { useEffect, useState, useRef } from "react";
import { Navigation } from "@/components/Navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { supabase } from "@/integrations/supabase/client";
import { Play } from "lucide-react";

interface Video {
  id: string;
  title: string;
  description: string;
  video_url: string;
  thumbnail_url: string;
  category: string;
}

import { isAdmin } from "@/lib/admin";
import { uploadFile } from "@/lib/storage";
import { useToast } from "@/hooks/use-toast";
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";

const Videos = () => {
  const [videos, setVideos] = useState<Video[]>([]);
  const [loading, setLoading] = useState(true);
  const [userEmail, setUserEmail] = useState<string | null>(null);
  const [adminMode, setAdminMode] = useState(false);

  // upload form state (minimal)
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [videoUrl, setVideoUrl] = useState("");
  const [thumbnailUrl, setThumbnailUrl] = useState("");
  const [category, setCategory] = useState("");
  const [thumbnailFile, setThumbnailFile] = useState<File | null>(null);
  const [videoFile, setVideoFile] = useState<File | null>(null);
  const [uploadLoading, setUploadLoading] = useState(false);

  // video player dialog
  const [playerOpen, setPlayerOpen] = useState(false);
  const [playerUrl, setPlayerUrl] = useState<string | null>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const [playerLoading, setPlayerLoading] = useState(false);

  // When player dialog opens, try to autoplay the video and show loading state.
  useEffect(() => {
    if (playerOpen && playerUrl) {
      // Delay slightly to ensure the video element is mounted
      const t = setTimeout(() => {
        const v = videoRef.current;
        if (!v) return;
        // Set loading until the video fires loaded events
        setPlayerLoading(true);
        // Attempt to play (may be blocked by autoplay policies)
        v.play().catch(() => {});
      }, 120);
      return () => clearTimeout(t);
    } else {
      setPlayerLoading(false);
    }
  }, [playerOpen, playerUrl]);

  const handleVideoClick = () => {
    const v = videoRef.current;
    if (!v) return;
    // Request fullscreen with vendor fallbacks
    const elem: any = v;
    if (elem.requestFullscreen) {
      elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
      elem.webkitRequestFullscreen();
    } else if (elem.mozRequestFullScreen) {
      elem.mozRequestFullScreen();
    } else if (elem.msRequestFullscreen) {
      elem.msRequestFullscreen();
    }
  };

  useEffect(() => {
    const fetchVideos = async () => {
      const { data, error } = await supabase
        .from("videos")
        .select("*")
        .order("created_at", { ascending: false });

      if (!error && data) {
        setVideos(data);
      }
      setLoading(false);
    };

    fetchVideos();
    // determine current user and admin status
    supabase.auth.getSession().then(({ data: { session } }) => {
      const email = session?.user?.email ?? null;
      setUserEmail(email);
      setAdminMode(isAdmin(email));
    });
  }, []);

  const { toast } = useToast();

  const handleUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!adminMode) return;
    try {
      setUploadLoading(true);
      // If files were provided, upload them to Supabase Storage first
      let finalThumbnail = thumbnailUrl;
      let finalVideoUrl = videoUrl;

      if (thumbnailFile) {
        const thumbPath = `thumbnails/${Date.now()}_${thumbnailFile.name}`;
        const uploadedThumb = await uploadFile('videos', thumbPath, thumbnailFile);
        if (uploadedThumb) finalThumbnail = uploadedThumb;
        else {
          toast({ title: 'Upload failed', description: 'Failed to upload thumbnail. Check console for details.', variant: 'destructive' });
          return;
        }
      }

      if (videoFile) {
        const vidPath = `videos/${Date.now()}_${videoFile.name}`;
        const uploadedVid = await uploadFile('videos', vidPath, videoFile);
        if (uploadedVid) finalVideoUrl = uploadedVid;
        else {
          toast({ title: 'Upload failed', description: 'Failed to upload video file. Check console for details.', variant: 'destructive' });
          return;
        }
      }

      // create directly in Supabase
      const { error } = await supabase.from('videos').insert([{ title, description, video_url: finalVideoUrl, thumbnail_url: finalThumbnail, category }]);
      if (error) {
        console.error('Supabase create video error', error);
        toast({ title: 'Create failed', description: error.message || 'Failed to create video record', variant: 'destructive' });
      } else {
        setTitle(""); setDescription(""); setVideoUrl(""); setThumbnailUrl(""); setCategory("");
        setThumbnailFile(null); setVideoFile(null);
        const { data } = await supabase.from("videos").select("*").order("created_at", { ascending: false });
        setVideos(data || []);
      }
    } catch (err: unknown) {
      console.error("Upload video error:", err);
    } finally {
      setUploadLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!adminMode) return;
    if (!confirm("Delete this video?")) return;
    try {
      const { error } = await supabase.from('videos').delete().eq('id', id);
      if (error) console.error('Supabase delete video error', error);
      else setVideos((v) => v.filter((x) => x.id !== id));
    } catch (err) {
      console.error('Delete video unexpected error', err);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-background via-muted/30 to-background">
      <Navigation />
      <main className="container mx-auto px-4 pt-24 pb-12">
        {adminMode && (
          <section className="max-w-3xl mx-auto mb-8 p-4 bg-card/60 rounded-md border border-primary/20">
            <div className="relative">
              {uploadLoading && (
                <div className="absolute inset-0 z-20 flex items-center justify-center bg-black/50 rounded-md">
                  <div className="text-center">
                    <svg className="animate-spin h-10 w-10 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <div className="mt-2 text-white">Uploading…</div>
                  </div>
                </div>
              )}
            <h2 className="text-lg font-semibold text-foreground mb-2">Admin: Upload Video</h2>
            <form onSubmit={handleUpload} className="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input id="video-title" name="title" className="p-2 bg-input text-foreground rounded" placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} required />
              <input id="video-category" name="category" className="p-2 bg-input text-foreground rounded" placeholder="Category" value={category} onChange={(e) => setCategory(e.target.value)} />
              <input id="video-url" name="video_url" className="p-2 col-span-2 bg-input text-foreground rounded" placeholder="Video URL (optional if uploading file)" value={videoUrl} onChange={(e) => setVideoUrl(e.target.value)} />
              <input id="video-file" name="video_file" type="file" accept="video/*" onChange={(e) => setVideoFile(e.target.files ? e.target.files[0] : null)} className="col-span-2" />
              <input id="thumbnail-url" name="thumbnail_url" className="p-2 col-span-2 bg-input text-foreground rounded" placeholder="Thumbnail URL (optional if uploading file)" value={thumbnailUrl} onChange={(e) => setThumbnailUrl(e.target.value)} />
              <input id="thumbnail-file" name="thumbnail_file" type="file" accept="image/*" onChange={(e) => setThumbnailFile(e.target.files ? e.target.files[0] : null)} className="col-span-2" />
              <textarea className="p-2 col-span-2 bg-input text-foreground rounded" placeholder="Description" value={description} onChange={(e) => setDescription(e.target.value)} />
              <button type="submit" disabled={uploadLoading} className="col-span-2 bg-primary text-primary-foreground p-2 rounded flex items-center justify-center">
                {uploadLoading ? (
                  <>
                    <svg className="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Uploading...
                  </>
                ) : (
                  'Upload'
                )}
              </button>
            </form>
            </div>
          </section>
        )}
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
            Hacking <span className="text-primary">Videos</span>
          </h1>
          <p className="text-muted-foreground text-lg">
            Learn from expert tutorials and demonstrations
          </p>
        </div>

        {/* Video player dialog (controlled) */}
        <Dialog open={playerOpen} onOpenChange={setPlayerOpen}>
          <DialogContent className="max-w-4xl w-full">
            <DialogTitle>Video player</DialogTitle>
            <DialogDescription>
              <div className="mt-4">
                {playerUrl ? (
                  <div className="relative">
                    <video
                      ref={videoRef}
                      src={playerUrl}
                      controls
                      autoPlay
                      className="w-full h-auto bg-black cursor-pointer"
                      onClick={handleVideoClick}
                      onLoadedData={() => setPlayerLoading(false)}
                      onCanPlay={() => setPlayerLoading(false)}
                      onWaiting={() => setPlayerLoading(true)}
                      onPlaying={() => setPlayerLoading(false)}
                    />
                    {playerLoading && (
                      <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                        <svg className="animate-spin h-12 w-12 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-center text-muted-foreground">No video selected</div>
                )}
              </div>
            </DialogDescription>
          </DialogContent>
        </Dialog>

        {loading ? (
          <div className="text-center text-primary text-lg">Loading videos...</div>
        ) : videos.length === 0 ? (
          <div className="text-center text-muted-foreground text-lg">
            No videos available yet. Check back soon!
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {videos.map((video) => (
              <Card key={video.id} className="bg-gradient-to-br from-card to-muted border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_30px_hsl(var(--cyber-glow)/0.3)] hover:-translate-y-1">
                <CardHeader>
                  <div
                    className="relative aspect-video bg-muted/50 rounded-lg overflow-hidden mb-4 group cursor-pointer"
                    role="button"
                    tabIndex={0}
                    onClick={() => {
                      if (video.video_url) {
                        setPlayerUrl(video.video_url);
                        setPlayerOpen(true);
                      } else {
                        toast({ title: 'No video', description: 'This video has no playable URL.', variant: 'default' });
                      }
                    }}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        if (video.video_url) {
                          setPlayerUrl(video.video_url);
                          setPlayerOpen(true);
                        }
                      }
                    }}
                  >
                    {video.thumbnail_url ? (
                      <img
                        src={video.thumbnail_url}
                        alt={video.title}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <Play className="h-16 w-16 text-primary" />
                      </div>
                    )}
                    <div className="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                      <Play className="h-20 w-20 text-primary drop-shadow-[0_0_15px_hsl(var(--cyber-glow))]" />
                    </div>
                  </div>
                  <CardTitle className="text-foreground">{video.title}</CardTitle>
                  {video.category && (
                    <span className="text-xs text-primary font-semibold">{video.category}</span>
                  )}
                </CardHeader>
                <CardContent>
                  <CardDescription className="text-muted-foreground">
                    {video.description}
                  </CardDescription>
                  {adminMode && (
                    <div className="mt-3">
                      <button className="text-sm text-destructive" onClick={() => handleDelete(video.id)}>Delete</button>
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>
    </div>
  );
};

export default Videos;