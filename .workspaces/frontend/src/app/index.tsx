// cd .workspaces/frontend && bun run dev
import FrontPage from "@react/tailwind/front-page";
// import ArchivePage from "@react/tailwind/archive";

import { posts, page } from "@context/data";

function App() {
  return (
    <>
      <FrontPage posts={posts} page={page} />
    </>
  )
}

export default App;