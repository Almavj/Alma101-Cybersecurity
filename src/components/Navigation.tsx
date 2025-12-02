import { Link, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Menu, X } from "lucide-react";
import { useState, useEffect } from "react";
import { supabase } from "@/integrations/supabase/client";
import { User } from "@supabase/supabase-js";

export const Navigation = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [user, setUser] = useState<User | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    supabase.auth.getSession().then(({ data: { session } }) => {
      setUser(session?.user ?? null);
    });

    const { data: { subscription } } = supabase.auth.onAuthStateChange((_event, session) => {
      setUser(session?.user ?? null);
    });

    return () => subscription.unsubscribe();
  }, []);

  const handleLogout = async () => {
    await supabase.auth.signOut();
    navigate("/");
  };

  return (
    <>
      <nav className="fixed top-0 w-full z-50 bg-background/95 backdrop-blur-md border-b border-primary/30 shadow-lg h-16">
        <div className="container mx-auto px-4 h-16 flex items-center justify-between">
            <Link to="/" className="flex items-center gap-3 group">
              <img src="/images/Alma101.png" alt="Alma101 logo" className="h-10 w-10 object-contain group-hover:drop-shadow-[0_0_12px_hsl(var(--cyber-glow))] transition-all" />
              <div className="flex flex-col">
                <span className="text-xl md:text-2xl font-bold text-foreground">Alma101 Security</span>
                <span className="text-xs text-muted-foreground italic">Protecting your digital assets</span>
              </div>
            </Link>

          {/* Desktop Menu */}
          <div className="hidden md:flex items-center gap-6">
            <Link to="/" className="text-foreground hover:text-primary transition-colors font-medium">
              Home
            </Link>
            <Link to="/videos" className="text-foreground hover:text-primary transition-colors font-medium">
              Hacking Videos
            </Link>
            <Link to="/blogs" className="text-foreground hover:text-primary transition-colors font-medium">
              Blog
            </Link>
            <Link to="/tools" className="text-foreground hover:text-primary transition-colors font-medium">
              Tools
            </Link>
            <Link to="/writeup" className="text-foreground hover:text-primary transition-colors font-medium">
              Writeups
            </Link>
            <Link to="/contact" className="text-foreground hover:text-primary transition-colors font-medium">
              Contact
            </Link>
            
            {user ? (
              <Button onClick={handleLogout} variant="outline" className="border-primary/50 text-primary hover:bg-primary hover:text-primary-foreground">
                Logout
              </Button>
            ) : (
              <Button asChild className="bg-primary text-primary-foreground hover:shadow-[0_0_20px_hsl(var(--cyber-glow))]">
                <Link to="/auth">Log In</Link>
              </Button>
            )}
          </div>

          {/* Mobile Menu Button */}
          <button
            className="md:hidden text-primary"
            onClick={() => setIsMenuOpen(!isMenuOpen)}
          >
            {isMenuOpen ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>

        {/* Mobile Menu */}
        {isMenuOpen && (
          <div className="md:hidden mt-4 pb-4 flex flex-col gap-4 border-t border-primary/30 pt-4">
            <Link to="/" className="text-foreground hover:text-primary transition-colors" onClick={() => setIsMenuOpen(false)}>
              Home
            </Link>
            <Link to="/videos" className="text-foreground hover:text-primary transition-colors" onClick={() => setIsMenuOpen(false)}>
              Hacking Videos
            </Link>
            <Link to="/blogs" className="text-foreground hover:text-primary transition-colors" onClick={() => setIsMenuOpen(false)}>
              Blog
            </Link>
            <Link to="/tools" className="text-foreground hover:text-primary transition-colors" onClick={() => setIsMenuOpen(false)}>
              Tools
            </Link>
            <Link to="/contact" className="text-foreground hover:text-primary transition-colors" onClick={() => setIsMenuOpen(false)}>
              Contact
            </Link>
            {/* Forgot-password flow is available on the login page (/auth) via the "Forgot password?" control */}
            
            {user ? (
              <Button onClick={handleLogout} variant="outline" className="border-primary text-primary">
                Logout
              </Button>
            ) : (
              <Button asChild>
                <Link to="/auth" onClick={() => setIsMenuOpen(false)}>Log In</Link>
              </Button>
            )}
          </div>
        )}
      </nav>

      {/* spacer so fixed header doesn't overlap page content */}
      <div aria-hidden="true" className="h-16 w-full" />
    </>
  );
};