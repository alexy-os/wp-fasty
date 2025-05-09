// cd .workspaces/frontend && bun run dev
//import { FrontPage } from "@uikits/latte/components/FrontPageTemplate";
//import { pageContext } from "@uikits/latte/data/context";
import { Card, CardHeader, CardTitle, CardDescription } from "./uikits/ui8px/core/components/card";

function App() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Card Title</CardTitle>
        <CardDescription>Card Description</CardDescription>
      </CardHeader>
    </Card>
  )
}

export default App;