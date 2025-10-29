import { useEffect, useState } from "react";
import { Navigation } from "@/components/Navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { supabase } from "@/integrations/supabase/client";
import { ExternalLink } from "lucide-react";

interface Tool {
  id: string;
  name: string;
  description: string;
  tool_url: string;
  category: string;
}

const Tools = () => {
  const [tools, setTools] = useState<Tool[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchTools = async () => {
      const { data, error } = await supabase
        .from("tools")
        .select("*")
        .order("created_at", { ascending: false });

      if (!error && data) {
        setTools(data);
      }
      setLoading(false);
    };

    fetchTools();
  }, []);

  return (
    <div className="min-h-screen bg-background">
      <Navigation />
      <main className="container mx-auto px-4 pt-24 pb-12">
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
            Hacking <span className="text-primary">Tools</span>
          </h1>
          <p className="text-muted-foreground text-lg">
            Essential tools for ethical hackers and security professionals
          </p>
        </div>

        {loading ? (
          <div className="text-center text-primary">Loading tools...</div>
        ) : tools.length === 0 ? (
          <div className="text-center text-muted-foreground">
            No tools available yet. Check back soon!
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {tools.map((tool) => (
              <Card key={tool.id} className="bg-card border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_20px_hsl(var(--cyber-glow)/0.3)]">
                <CardHeader>
                  <CardTitle className="text-foreground">{tool.name}</CardTitle>
                  {tool.category && (
                    <span className="text-xs text-primary">{tool.category}</span>
                  )}
                </CardHeader>
                <CardContent className="space-y-4">
                  <CardDescription className="text-muted-foreground">
                    {tool.description}
                  </CardDescription>
                  {tool.tool_url && (
                    <Button
                      asChild
                      variant="outline"
                      className="w-full border-primary text-primary hover:bg-primary hover:text-primary-foreground"
                    >
                      <a href={tool.tool_url} target="_blank" rel="noopener noreferrer">
                        Access Tool <ExternalLink className="ml-2 h-4 w-4" />
                      </a>
                    </Button>
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

export default Tools;