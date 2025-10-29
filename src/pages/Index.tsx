import { Navigation } from "@/components/Navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Shield, Video, BookOpen, Wrench, Lock } from "lucide-react";
import { Link } from "react-router-dom";
import heroImage from "@/assets/hero-cyber.jpg";

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navigation />
      
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 px-4">
        <div className="absolute inset-0 overflow-hidden">
          <img 
            src={heroImage} 
            alt="Cybersecurity hero background" 
            className="w-full h-full object-cover opacity-30"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-background/50 via-background/80 to-background"></div>
        </div>
        
        <div className="container mx-auto relative z-10">
          <div className="max-w-4xl mx-auto text-center space-y-6">
            <h1 className="text-5xl md:text-7xl font-bold text-foreground drop-shadow-[0_0_30px_hsl(var(--cyber-glow))]">
              Welcome to <span className="text-primary">Alma101 Hackings</span>
            </h1>
            <p className="text-xl md:text-2xl text-muted-foreground">
              Master cybersecurity and ethical hacking with expert tutorials, tools, and insights
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center pt-6">
              <Button 
                asChild 
                size="lg" 
                className="bg-primary text-primary-foreground hover:shadow-[0_0_30px_hsl(var(--cyber-glow))] transition-all"
              >
                <Link to="/auth">Get Started</Link>
              </Button>
              <Button 
                asChild 
                size="lg" 
                variant="outline"
                className="border-primary text-primary hover:bg-primary hover:text-primary-foreground"
              >
                <Link to="/contact">Contact Us</Link>
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 px-4 bg-gradient-to-b from-background to-muted/20">
        <div className="container mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl md:text-5xl font-bold text-foreground mb-4">
              What We <span className="text-primary">Offer</span>
            </h2>
            <p className="text-muted-foreground text-lg">
              Everything you need to become a cybersecurity expert
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <Card className="bg-card border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_20px_hsl(var(--cyber-glow)/0.3)]">
              <CardHeader>
                <div className="flex items-center gap-3">
                  <Video className="h-10 w-10 text-primary" />
                  <CardTitle className="text-foreground">Hacking Videos</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-muted-foreground">
                  Learn from expert tutorials covering penetration testing, network security, and advanced hacking techniques.
                </CardDescription>
                <Button asChild variant="link" className="text-primary mt-4 p-0">
                  <Link to="/videos">
                    Explore Videos <Lock className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </CardContent>
            </Card>

            <Card className="bg-card border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_20px_hsl(var(--cyber-glow)/0.3)]">
              <CardHeader>
                <div className="flex items-center gap-3">
                  <BookOpen className="h-10 w-10 text-primary" />
                  <CardTitle className="text-foreground">Security Blogs</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-muted-foreground">
                  Stay updated with the latest cybersecurity trends, vulnerabilities, and best practices from industry experts.
                </CardDescription>
                <Button asChild variant="link" className="text-primary mt-4 p-0">
                  <Link to="/blogs">
                    Read Blogs <Lock className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </CardContent>
            </Card>

            <Card className="bg-card border-primary/30 hover:border-primary transition-all hover:shadow-[0_0_20px_hsl(var(--cyber-glow)/0.3)]">
              <CardHeader>
                <div className="flex items-center gap-3">
                  <Wrench className="h-10 w-10 text-primary" />
                  <CardTitle className="text-foreground">Hacking Tools</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-muted-foreground">
                  Access curated collection of essential penetration testing and security assessment tools.
                </CardDescription>
                <Button asChild variant="link" className="text-primary mt-4 p-0">
                  <Link to="/tools">
                    View Tools <Lock className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 px-4">
        <div className="container mx-auto">
          <Card className="bg-gradient-to-r from-card to-muted/20 border-primary/50 max-w-4xl mx-auto">
            <CardHeader className="text-center pb-8">
              <Shield className="h-16 w-16 text-primary mx-auto mb-4 drop-shadow-[0_0_12px_hsl(var(--cyber-glow))]" />
              <CardTitle className="text-3xl md:text-4xl text-foreground mb-4">
                Ready to Start Your Journey?
              </CardTitle>
              <CardDescription className="text-muted-foreground text-lg">
                Join our community of ethical hackers and cybersecurity professionals
              </CardDescription>
            </CardHeader>
            <CardContent className="text-center">
              <Button 
                asChild 
                size="lg"
                className="bg-primary text-primary-foreground hover:shadow-[0_0_30px_hsl(var(--cyber-glow))]"
              >
                <Link to="/auth">Create Free Account</Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-8 px-4 border-t border-primary/20">
        <div className="container mx-auto text-center text-muted-foreground">
          <p>&copy; 2024 Alma101 Hackings. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
};

export default Index;