import { getComponents, getUI } from "@/utils/theme";
import { site, page } from "@/context/data";

export type SidebarProps = {
  className?: string
}

export function Sidebar({ className }: SidebarProps) {
  const {
    P,
    Aside
  } = getComponents();

  const { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFigure, CardImage, CardFigcaption } = getUI();

  return (
    <Aside className={className}>
      <div className="flex flex-col gap-6">
        <Card>
          {page.featuredImage && (
            <CardFigure>
              <CardImage
                src={page.featuredImage.url}
                alt={page.featuredImage.alt}
              />
              {page.featuredImage.caption && (
                <CardFigcaption>{page.featuredImage.caption}</CardFigcaption>
              )}
            </CardFigure>
          )}

          <CardContent>
            <CardTitle>{page.title}</CardTitle>
            <CardDescription>{page.content}</CardDescription>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Sidebar</CardTitle>
          </CardHeader>
          <CardContent>
            <P>Sidebar</P>
          </CardContent>
        </Card>
        <Card>
          <CardContent>
            <P className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</P>
          </CardContent>
        </Card>
      </div>
    </Aside>
  );
}
