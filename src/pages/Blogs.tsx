import { useEffect, useState } from "react";
import DOMPurify from "dompurify";
import { Navigation } from "@/components/Navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { supabase } from "@/integrations/supabase/client";
import { Calendar } from "lucide-react";
import { format } from "date-fns";
import { isAdmin } from "@/lib/admin";

interface Blog {
  id: string;
  title: string;
  excerpt: string;
  content: string;
  image_url: string;
  link?: string;
  author_id?: string;
  published?: boolean;
  created_at: string;
}

const Blogs = () => {
  const [blogs, setBlogs] = useState<Blog[]>([]);
  const [loading, setLoading] = useState(true);
  const [adminMode, setAdminMode] = useState(false);
  const [userId, setUserId] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<string | null>(null);

  // upload state
  const [title, setTitle] = useState("");
  const [excerpt, setExcerpt] = useState("");
  const [content, setContent] = useState("");
  const [imageUrl, setImageUrl] = useState("");
  const [link, setLink] = useState("");

  useEffect(() => {
    const fetchBlogs = async () => {
      const { data, error } = await supabase
        .from("blogs")
        .select("*")
        .order("created_at", { ascending: false });

      if (!error && data) {
        setBlogs(data);
      }
      setLoading(false);
    };

    fetchBlogs();
    // Determine admin mode client-side (consistent with Tools/Videos pages)
    supabase.auth.getSession().then(({ data: { session } }) => {
      setUserId(session?.user?.id ?? null);
      setAdminMode(isAdmin(session?.user?.email ?? null));
    });
  }, []);

  const handleUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!adminMode) return;
    try {
      let finalImage = imageUrl;
      // create directly in Supabase. Use image_url for image and include author_id + published flag.
      const payload: any = { title, excerpt, content, image_url: finalImage, published: false };
      if (link) payload.link = link;
      // Use the current authenticated user from Supabase to set author_id at submit time
      const { data: userData } = await supabase.auth.getUser();
      const currentUser = userData?.user ?? null;
      if (!currentUser) {
        alert('You must be logged in to create a blog!');
        return;
      }
      // Use the authenticated user's id as author_id (adminMode already checked)
      payload.author_id = currentUser.id;

      const { error } = await supabase.from('blogs').insert([payload]).select();
      if (error) {
        console.error('Supabase create blog error', error);
      } else {
        setTitle(""); setExcerpt(""); setContent(""); setImageUrl(""); setLink("");
        const { data } = await supabase.from("blogs").select("*").order("created_at", { ascending: false });
        setBlogs(data || []);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleDelete = async (id: string) => {
    if (!adminMode) return;
    if (!confirm("Delete this blog post?")) return;
    try {
      const { error } = await supabase.from('blogs').delete().eq('id', id);
      if (error) console.error('Supabase delete blog error', error);
      else setBlogs((b) => b.filter((x) => x.id !== id));
    } catch (err) {
      console.error('Delete blog unexpected error', err);
    }
  };

  const renderBlogContent = (text?: string) => {
    if (!text) return null;
    // If the content contains HTML tags, treat it as HTML and sanitize before injecting.
    const looksLikeHtml = /<[^>]+>/g.test(text);
    if (looksLikeHtml) {
      // Sanitize the incoming HTML to prevent XSS.
      const clean = DOMPurify.sanitize(text, { ADD_ATTR: ['target'] });
      return <div dangerouslySetInnerHTML={{ __html: clean }} />;
    }

    const isImageUrl = (u: string) => /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(u);
    const isUrl = (u: string) => /^https?:\/\//i.test(u);

    return text.split("\n").map((line, lineIdx) => (
      <p key={lineIdx} className="mb-2">
        {line.split(/(\s+)/).map((token, i) => {
          if (!token) return null;
          if (isImageUrl(token)) {
            return (
              <img key={i} src={token} alt="blog-inline" className="max-w-full rounded my-2" />
            );
          }
          if (isUrl(token)) {
            return (
              <a key={i} href={token} target="_blank" rel="noopener noreferrer" className="text-primary underline">
                {token}
              </a>
            );
          }
          return <span key={i}>{token}</span>;
        })}
      </p>
    ));
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-background via-muted/30 to-background">
      <Navigation />
      <main className="container mx-auto px-4 pt-24 pb-12">
        {adminMode && (
          <section className="max-w-3xl mx-auto mb-8 p-4 bg-card/60 rounded-md border border-primary/20">
            <h2 className="text-lg font-semibold text-foreground mb-2">Admin: Publish Blog</h2>
            <form onSubmit={handleUpload} className="grid grid-cols-1 gap-2">
              <input className="p-2 bg-input text-foreground rounded" placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} required />
              <input className="p-2 bg-input text-foreground rounded" placeholder="Excerpt" value={excerpt} onChange={(e) => setExcerpt(e.target.value)} />
              <input id="blog-image-url" name="image_url" className="p-2 bg-input text-foreground rounded" placeholder="Image URL (optional)" value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} />
              <input id="blog-link" name="link" className="p-2 bg-input text-foreground rounded" placeholder="External link (optional)" value={link} onChange={(e) => setLink(e.target.value)} />
              <textarea className="p-2 bg-input text-foreground rounded" placeholder="Content" value={content} onChange={(e) => setContent(e.target.value)} />
              <button type="submit" className="bg-primary text-primary-foreground p-2 rounded">Publish</button>
            </form>
          </section>
        )}
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
            Security <span className="text-primary">Insights</span>
          </h1>
          <p className="text-muted-foreground text-lg">
            Latest articles on cybersecurity and ethical hacking
          </p>
        </div>

        {loading ? (
          <div className="text-center text-primary text-lg">Loading blogs...</div>
        ) : blogs.length === 0 ? (
          <div className="text-center text-muted-foreground text-lg">
            No blog posts available yet. Check back soon!
          </div>
        ) : (
          <div className="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            {blogs.map((blog) => (
              <Card key={blog.id} className="bg-gradient-to-br from-card to-muted border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_30px_hsl(var(--cyber-glow)/0.3)] hover:-translate-y-1">
                <CardHeader>
                  {blog.image_url && (
                    <div className="aspect-video bg-muted/50 rounded-lg overflow-hidden mb-4">
                      <img
                        src={blog.image_url}
                        alt={blog.title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  )}
                  <CardTitle className="text-foreground text-xl">{blog.title}</CardTitle>
                  <div className="flex items-center gap-2 text-muted-foreground text-sm">
                    <Calendar className="h-4 w-4" />
                    <span>{format(new Date(blog.created_at), "MMM dd, yyyy")}</span>
                  </div>
                </CardHeader>
                <CardContent>
                  {expandedId === blog.id ? (
                    <div className="prose max-w-none text-foreground text-muted-foreground">
                      {renderBlogContent(blog.content)}
                    </div>
                  ) : (
                    <CardDescription className="text-muted-foreground">
                      {blog.excerpt || (blog.content ? blog.content.substring(0, 150) + "..." : "")}
                    </CardDescription>
                  )}
                  <div className="mt-3">
                    <button
                      className="text-sm text-primary underline"
                      onClick={() => setExpandedId(expandedId === blog.id ? null : blog.id)}
                    >
                      {expandedId === blog.id ? "Show less" : "Read more"}
                    </button>
                  </div>
                  {blog.link && (
                    <div className="mt-3">
                      <a href={blog.link} target="_blank" rel="noopener noreferrer" className="text-primary underline">Read full article</a>
                    </div>
                  )}
                  {adminMode && (
                    <div className="mt-3">
                      <button className="text-sm text-destructive" onClick={() => handleDelete(blog.id)}>Delete</button>
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

export default Blogs;