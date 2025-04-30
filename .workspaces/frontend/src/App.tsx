import { Button } from "@infobiz/ui/button";
import { Badge } from "@infobiz/ui/badge";
import { Card } from "@infobiz/components/card";
import { BusinessmanCard } from "@infobiz/components/businessman-card";
import { VideoCard } from "@infobiz/components/video-card";
import { Hero } from "@infobiz/blocks/hero";
import { Features } from "@infobiz/blocks/features";

function App() {
  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Infobiz UI Kit Demo</h1>

      {/* UI components section */}
      <section className="mb-12">
        <h2 className="text-2xl font-semibold mb-4">UI Components (Atoms)</h2>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Buttons</h3>
          <div className="flex flex-wrap gap-4">
            <Button>Default</Button>
            <Button variant="primary">Primary</Button>
            <Button variant="secondary">Secondary</Button>
            <Button variant="outline">Outline</Button>
            <Button variant="ghost">Ghost</Button>
            <Button size="sm">Small</Button>
            <Button size="lg">Large</Button>
          </div>
        </div>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Badges</h3>
          <div className="flex flex-wrap gap-4">
            <Badge>Default</Badge>
            <Badge variant="primary">Primary</Badge>
            <Badge variant="secondary">Secondary</Badge>
            <Badge variant="outline">Outline</Badge>
            <Badge size="sm">Small</Badge>
            <Badge size="lg">Large</Badge>
          </div>
        </div>
      </section>

      {/* Components section */}
      <section className="mb-12">
        <h2 className="text-2xl font-semibold mb-4">Components (Molecules)</h2>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Cards</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div className="p-4">
              <Card variant="default">
                <h3 className="text-lg font-semibold mb-2">Basic Card</h3>
                <p>This is a simple card component with content inside.</p>
              </Card>
            </div>

            <div className="p-4">
              <Card variant="hover">
                <h3 className="text-lg font-semibold mb-2">Hover Card</h3>
                <p>This card has a hover effect. Try hovering over it!</p>
              </Card>
            </div>

            <div className="p-4">
              <Card variant="outline">
                <h3 className="text-lg font-semibold mb-2">Outline Card</h3>
                <p>This is a card with an outline style.</p>
              </Card>
            </div>
          </div>
        </div>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Businessman Cards</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <BusinessmanCard
              name="John Doe"
              category="Business Strategy"
              revenue="$1.2M"
              subscribers="15K"
              tags={["Marketing", "Leadership"]}
              image="https://via.placeholder.com/150"
              profileUrl="#john-doe"
            />

            <BusinessmanCard
              name="Jane Smith"
              category="Digital Marketing"
              revenue="$950K"
              subscribers="12K"
              tags={["SEO", "Content"]}
              image="https://via.placeholder.com/150"
              profileUrl="#jane-smith"
              variant="featured"
            />
          </div>
        </div>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Video Cards</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <VideoCard
              title="Introduction to Business"
              description="Learn the fundamentals of business management."
              author="Business Academy"
              authorImage="https://via.placeholder.com/50"
              authorUrl="#business-academy"
              image="https://via.placeholder.com/300x200"
              duration="45:20"
              platform="YouTube"
              views="15K"
              publishedAt="2023-06-15"
              url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
            />

            <VideoCard
              title="Marketing Strategies"
              description="Discover effective marketing approaches for your business."
              author="Marketing Pro"
              authorImage="https://via.placeholder.com/50"
              authorUrl="#marketing-pro"
              image="https://via.placeholder.com/300x200"
              duration="32:45"
              platform="YouTube"
              views="8.5K"
              publishedAt="2023-08-22"
              url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
              variant="featured"
            />
          </div>
        </div>
      </section>

      {/* Blocks section */}
      <section className="mb-12">
        <h2 className="text-2xl font-semibold mb-4">Blocks (Organisms)</h2>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Hero Section</h3>
          <Hero
            title="Transform Your Business"
            subtitle="Proven strategies for success"
            description="Join thousands of entrepreneurs who have taken their business to the next level with our proven strategies and tools."
            backgroundImage="https://via.placeholder.com/1200x600"
            accentColor="primary"
          />
        </div>

        <div className="mb-8">
          <h3 className="text-xl mb-3">Features Section</h3>
          <Features
            title="Our Key Features"
            description="Discover why businesses choose our platform for their growth and success."
            features={[
              {
                title: "Expert Guidance",
                description: "Get personalized advice from industry experts.",
                icon: "🧠"
              },
              {
                title: "Proven Strategies",
                description: "Access battle-tested methods that deliver results.",
                icon: "📈"
              },
              {
                title: "Community Support",
                description: "Join a network of like-minded entrepreneurs.",
                icon: "👥"
              },
              {
                title: "Comprehensive Resources",
                description: "Utilize our extensive library of tools and templates.",
                icon: "🛠️"
              }
            ]}
          />
        </div>
      </section>
    </div>
  );
}

export default App;