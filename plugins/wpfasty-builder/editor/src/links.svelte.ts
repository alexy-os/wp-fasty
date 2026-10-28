import { createContext } from "svelte";
import type { LinkIndex } from "./links";

/** the link picker data of the editor session: GET /links once, shared by every link field (retried after an error) */
export class LinkStore {
  index = $state.raw<LinkIndex | null>(null);
  error = $state("");
  #pending: Promise<void> | null = null;
  #load: () => Promise<LinkIndex>;

  constructor(load: () => Promise<LinkIndex>) {
    this.#load = load;
  }

  /** loads the index unless it is loaded or loading */
  ensure(): Promise<void> {
    this.#pending ??= this.#load().then(
      (index) => {
        this.index = index;
        this.error = "";
      },
      (e: unknown) => {
        this.error = e instanceof Error ? e.message : String(e);
        this.#pending = null;
      },
    );
    return this.#pending;
  }
}

export const [getLinks, setLinks] = createContext<LinkStore>();
