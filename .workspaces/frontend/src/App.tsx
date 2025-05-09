// cd .workspaces/frontend && bun run dev
//import { FrontPage } from "@uikits/latte/components/FrontPageTemplate";
//import { pageContext } from "@uikits/latte/data/context";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter, CardAction } from "@uikits/ui8px/core/semantic/components/card";

function App() {
  return (
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
  )
}

export default App;