import { useEffect, useState } from "react";
import { Navigation } from "@/components/Navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { supabase } from "@/integrations/supabase/client";
import { Calendar } from "lucide-react";
import { format } from "date-fns";

interface Blog {
  id: string;
  title: string;
  excerpt: string;
  content: string;
  image_url: string;
  created_at: string;
}

const Blogs = () => {
  const [blogs, setBlogs] = useState<Blog[]>([]);
  const [loading, setLoading] = useState(true);

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
  }, []);

  return (
    <div className="min-h-screen bg-background">
      <Navigation />
      <main className="container mx-auto px-4 pt-24 pb-12">
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
            Security <span className="text-primary">Insights</span>
          </h1>
          <p className="text-muted-foreground text-lg">
            Latest articles on cybersecurity and ethical hacking
          </p>
        </div>

        {loading ? (
          <div className="text-center text-primary">Loading blogs...</div>
        ) : blogs.length === 0 ? (
          <div className="text-center text-muted-foreground">
            No blog posts available yet. Check back soon!
          </div>
        ) : (
          <div className="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            {blogs.map((blog) => (
              <Card key={blog.id} className="bg-card border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_20px_hsl(var(--cyber-glow)/0.3)]">
                <CardHeader>
                  {blog.image_url && (
                    <div className="aspect-video bg-muted rounded-lg overflow-hidden mb-4">
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
                  <CardDescription className="text-muted-foreground">
                    {blog.excerpt || blog.content.substring(0, 150) + "..."}
                  </CardDescription>
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