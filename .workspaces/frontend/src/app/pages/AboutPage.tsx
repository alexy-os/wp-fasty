import { RootLayout } from '../layouts/RootLayout'

export function AboutPage() {
  return (
    <RootLayout title="About Our Approach">
      <div className="prose lg:prose-xl mx-auto">
        <h1 className="text-4xl font-bold mb-6">Our Modern Web Architecture</h1>
        {/* {page.featuredImage && (
          <img
            src={page.featuredImage.url}
            alt={page.featuredImage.alt}
            className="w-full h-auto mb-8 rounded-lg shadow-lg"
          />
        )} */}
        <div className="content">
          <p>At our core, we believe in building web applications that respect both users and developers. Our approach combines the best of modern tooling with timeless web principles.</p>

          <h2 className="text-2xl font-bold mb-2">Performance First</h2>
          <p>By leveraging Elysia with Bun and server-side rendering, we deliver lightning-fast initial page loads. Static HTML generation means users see content immediately, regardless of their device or connection speed.</p>

          <h2 className="text-2xl font-bold mb-2">Progressive Enhancement</h2>
          <p>Rather than shipping megabytes of JavaScript upfront, we enhance interactivity precisely where needed. Through strategic use of HTMX, Web Components, and WebAssembly islands, we maintain speed while adding rich functionality.</p>

          <h3 className="text-xl font-bold mb-2">Future-Proofed Components</h3>
          <p>Our React component architecture serves as a universal asset. Components remain portable across frameworks, providing insurance against changing technology landscapes and enabling seamless migration paths if needed.</p>

          <h3 className="text-xl font-bold mb-2">SEO Without Compromise</h3>
          <p>Search engines receive clean, semantic HTML without waiting for JavaScript execution. This approach ensures optimal indexing while maintaining the development experience of modern frameworks.</p>

          <p>This balanced architecture delivers exceptional user experiences today while establishing a foundation that can evolve with both web standards and business requirements.</p>
        </div>
      </div>
    </RootLayout>
  )
} 