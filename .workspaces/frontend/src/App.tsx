// cd .workspaces/frontend && bun run dev
//import { LandingPageExample } from "@uikits/ui8px/examples/landing-page-example";
import { FrontPage } from "@uikits/latte/components/FrontPageTemplate";
import { pageContext } from "@uikits/latte/data/context";

function App() {
  return (
    <FrontPage {...pageContext} />
  )
}

export default App;