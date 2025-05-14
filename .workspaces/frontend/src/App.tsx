// cd .workspaces/frontend && bun run dev
import FrontPage from "./templates/react/source/front-page";
// import ArchivePage from "./templates/react/source/archive";

import { posts, page } from "./context/data";

function App() {
  return (
    <>
      <FrontPage posts={posts} page={page} />
    </>
  )
}

export default App;