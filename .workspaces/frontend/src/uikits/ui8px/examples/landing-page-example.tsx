import React from 'react';

// Import components from the UI Kit
import { Button } from '../core/ui/button';
import { Card } from '../core/ui/card';
import { Avatar } from '../core/ui/avatar';
import { Badge } from '../core/ui/badge';
import { Link } from '../core/ui/link';
import { Heading, Text } from '../core/ui/typography';

/**
 * Example Landing Page using the UI8px design system
 * Uses semantic HTML5 elements without utility classes
 */
export const LandingPageExample: React.FC = () => {
  return (
    <div className="landing-page">
      <header className="site-header">
        <div className="container">
          <nav className="main-navigation">
            <div className="logo">
              <Heading level="h1" weight="semibold">UI8px</Heading>
            </div>

            <ul className="nav-links">
              <li className="nav-item">
                <Link href="#features">Features</Link>
              </li>
              <li className="nav-item">
                <Link href="#testimonials">Testimonials</Link>
              </li>
              <li className="nav-item">
                <Link href="#pricing">Pricing</Link>
              </li>
              <li className="nav-item">
                <Button variant="primary" size="sm">Get Started</Button>
              </li>
            </ul>
          </nav>
        </div>
      </header>

      <main className="site-content">
        {/* Hero Section */}
        <section className="hero-section" id="hero">
          <div className="container">
            <div className="hero-content">
              <Heading level="h2" weight="bold">Modern Design System for Modern Web</Heading>

              <Text size="lg">
                Build beautiful interfaces with our 8px grid design system that scales across all devices.
              </Text>

              <div className="hero-actions">
                <Button variant="primary" size="lg">Start Free Trial</Button>
                <Button variant="outline" size="lg">Documentation</Button>
              </div>

              <div className="hero-badges">
                <Badge variant="primary">8px Grid</Badge>
                <Badge variant="secondary">OKLCH Colors</Badge>
                <Badge variant="outline">Dark Mode</Badge>
              </div>
            </div>

            <div className="hero-image">
              {/* Placeholder for hero image */}
              <div className="image-placeholder">
                <Text>UI Showcase Image</Text>
              </div>
            </div>
          </div>
        </section>

        {/* Features Section */}
        <section className="features-section" id="features">
          <div className="container">
            <header className="section-header">
              <Heading level="h2">Key Features</Heading>
              <Text>What makes UI8px special</Text>
            </header>

            <ul className="features-list">
              <li className="feature-item">
                <Card variant="outline">
                  <article className="feature">
                    <header className="feature-header">
                      <div className="feature-icon">
                        {/* Icon placeholder */}
                      </div>
                      <Heading level="h3">8px Grid System</Heading>
                    </header>
                    <div className="feature-body">
                      <Text>Consistent spacing with an 8-point grid for pixel-perfect layouts across all screen sizes.</Text>
                    </div>
                  </article>
                </Card>
              </li>

              <li className="feature-item">
                <Card variant="outline">
                  <article className="feature">
                    <header className="feature-header">
                      <div className="feature-icon">
                        {/* Icon placeholder */}
                      </div>
                      <Heading level="h3">Semantic Components</Heading>
                    </header>
                    <div className="feature-body">
                      <Text>Components named for their meaning rather than appearance, making your code more maintainable.</Text>
                    </div>
                  </article>
                </Card>
              </li>

              <li className="feature-item">
                <Card variant="outline">
                  <article className="feature">
                    <header className="feature-header">
                      <div className="feature-icon">
                        {/* Icon placeholder */}
                      </div>
                      <Heading level="h3">Dark Mode Support</Heading>
                    </header>
                    <div className="feature-body">
                      <Text>Built-in dark mode support with optimized contrast ratios for better accessibility.</Text>
                    </div>
                  </article>
                </Card>
              </li>
            </ul>
          </div>
        </section>

        {/* Testimonials Section */}
        <section className="testimonials-section" id="testimonials">
          <div className="container">
            <header className="section-header">
              <Heading level="h2">What Our Users Say</Heading>
              <Text>Trusted by developers worldwide</Text>
            </header>

            <ul className="testimonials-list">
              <li className="testimonial-item">
                <Card>
                  <article className="testimonial">
                    <blockquote className="testimonial-content">
                      <Text>"UI8px has transformed how we build interfaces. The 8px grid system ensures consistency across our entire product."</Text>
                    </blockquote>
                    <footer className="testimonial-author">
                      <Avatar size="sm" initials="JD" />
                      <div className="author-info">
                        <Heading level="h3" weight="medium">Jane Doe</Heading>
                        <Text size="sm">Lead Developer, TechCorp</Text>
                      </div>
                    </footer>
                  </article>
                </Card>
              </li>

              <li className="testimonial-item">
                <Card>
                  <article className="testimonial">
                    <blockquote className="testimonial-content">
                      <Text>"The semantic naming conventions make our codebase so much cleaner and more maintainable."</Text>
                    </blockquote>
                    <footer className="testimonial-author">
                      <Avatar size="sm" initials="MS" />
                      <div className="author-info">
                        <Heading level="h3" weight="medium">Mark Smith</Heading>
                        <Text size="sm">UI Designer, DesignStudio</Text>
                      </div>
                    </footer>
                  </article>
                </Card>
              </li>
            </ul>
          </div>
        </section>

        {/* Pricing Section */}
        <section className="pricing-section" id="pricing">
          <div className="container">
            <header className="section-header">
              <Heading level="h2">Simple Pricing</Heading>
              <Text>Choose the plan that works for you</Text>
            </header>

            <ul className="pricing-list">
              <li className="pricing-item">
                <Card variant="outline">
                  <article className="pricing-plan">
                    <header className="plan-header">
                      <Heading level="h3">Starter</Heading>
                      <div className="plan-price">
                        <Text size="xl" weight="bold">$0</Text>
                        <Text size="sm">/month</Text>
                      </div>
                    </header>

                    <ul className="plan-features">
                      <li className="plan-feature">Core components</li>
                      <li className="plan-feature">Basic support</li>
                      <li className="plan-feature">Community access</li>
                    </ul>

                    <footer className="plan-footer">
                      <Button variant="outline" size="lg" className="plan-button">Get Started</Button>
                    </footer>
                  </article>
                </Card>
              </li>

              <li className="pricing-item">
                <Card variant="primary">
                  <article className="pricing-plan pricing-plan-featured">
                    <Badge variant="primary" className="featured-badge">Popular</Badge>

                    <header className="plan-header">
                      <Heading level="h3">Pro</Heading>
                      <div className="plan-price">
                        <Text size="xl" weight="bold">$29</Text>
                        <Text size="sm">/month</Text>
                      </div>
                    </header>

                    <ul className="plan-features">
                      <li className="plan-feature">All components</li>
                      <li className="plan-feature">Priority support</li>
                      <li className="plan-feature">Premium templates</li>
                      <li className="plan-feature">Advanced customization</li>
                    </ul>

                    <footer className="plan-footer">
                      <Button size="lg" className="plan-button">Start Free Trial</Button>
                    </footer>
                  </article>
                </Card>
              </li>

              <li className="pricing-item">
                <Card variant="outline">
                  <article className="pricing-plan">
                    <header className="plan-header">
                      <Heading level="h3">Enterprise</Heading>
                      <div className="plan-price">
                        <Text size="xl" weight="bold">$99</Text>
                        <Text size="sm">/month</Text>
                      </div>
                    </header>

                    <ul className="plan-features">
                      <li className="plan-feature">All Pro features</li>
                      <li className="plan-feature">Dedicated support</li>
                      <li className="plan-feature">Custom branding</li>
                      <li className="plan-feature">Team collaboration</li>
                    </ul>

                    <footer className="plan-footer">
                      <Button variant="outline" size="lg" className="plan-button">Contact Sales</Button>
                    </footer>
                  </article>
                </Card>
              </li>
            </ul>
          </div>
        </section>

        {/* CTA Section */}
        <section className="cta-section">
          <div className="container">
            <div className="cta-content">
              <Heading level="h2">Ready to get started?</Heading>
              <Text size="lg">Join thousands of developers building modern interfaces with UI8px.</Text>
              <Button variant="primary" size="lg">Start Free Trial</Button>
            </div>
          </div>
        </section>
      </main>

      <footer className="site-footer">
        <div className="container">
          <div className="footer-content">
            <div className="footer-logo">
              <Heading level="h2">UI8px</Heading>
              <Text>Modern Design System for Modern Web</Text>
            </div>

            <nav className="footer-navigation">
              <div className="footer-nav-section">
                <Heading level="h3" weight="medium">Product</Heading>
                <ul className="footer-nav-list">
                  <li><Link href="#features">Features</Link></li>
                  <li><Link href="#pricing">Pricing</Link></li>
                  <li><Link href="#">Documentation</Link></li>
                </ul>
              </div>

              <div className="footer-nav-section">
                <Heading level="h3" weight="medium">Company</Heading>
                <ul className="footer-nav-list">
                  <li><Link href="#">About</Link></li>
                  <li><Link href="#">Blog</Link></li>
                  <li><Link href="#">Careers</Link></li>
                </ul>
              </div>

              <div className="footer-nav-section">
                <Heading level="h3" weight="medium">Resources</Heading>
                <ul className="footer-nav-list">
                  <li><Link href="#">Support</Link></li>
                  <li><Link href="#">Community</Link></li>
                  <li><Link href="#">Contact</Link></li>
                </ul>
              </div>
            </nav>
          </div>

          <div className="footer-bottom">
            <Text size="sm">© 2023 UI8px. All rights reserved.</Text>
            <div className="footer-links">
              <Link href="#" variant="muted">Privacy Policy</Link>
              <Link href="#" variant="muted">Terms of Service</Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}; 