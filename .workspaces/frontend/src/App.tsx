// cd .workspaces/frontend && bun run dev
//import { FrontPage } from "@uikits/latte/components/FrontPageTemplate";
//import { pageContext } from "@uikits/latte/data/context";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter, CardAction } from "@uikits/ui8px/core/semantic/components/card";
import { Button } from "@uikits/ui8px/core/semantic/ui/button";
import { Badge } from "@uikits/ui8px/core/semantic/ui/badge";
import { Table, TableHeader, TableBody, TableRow, TableCell } from "@uikits/ui8px/core/semantic/components/table";

function App() {
  return (
    <>
      <Button variant="destructive" size="sm" asChild>
        <a href="https://www.google.com">Button</a>
      </Button>
      <Badge>Badge</Badge>
      <Card>
        <CardHeader>
          <CardTitle>Card Title</CardTitle>
          <CardDescription>Card Description</CardDescription>
        </CardHeader>
        <CardContent>
          <CardDescription>Card Description</CardDescription>
        </CardContent>
        <CardFooter>
          <CardAction>Card Action</CardAction>
        </CardFooter>
      </Card>
      <Table>
        <TableHeader>
          <TableRow>
            <TableCell>Table Cell</TableCell>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow>
            <TableCell>Table Cell</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </>
  )
}

export default App;