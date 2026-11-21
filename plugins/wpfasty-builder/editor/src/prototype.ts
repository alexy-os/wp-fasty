import { previewSource } from "./preview";

/** Same nonce/capability-protected page URL as the real canvas. The sheet contains static DOM, :target selects the
 * variant; scheme is request-local (never written to the theme preference). No scripts or synthetic srcdoc. */
export function prototypeSource(base: string, item: string, variant: string, dark: boolean): string {
  if (!base || !/^[a-z][a-z0-9-]{0,62}$/.test(item) || !/^[A-Za-z][A-Za-z0-9_-]{0,62}$/.test(variant)) return "";
  const url = new URL(previewSource(base, 0, 0, dark ? "dark" : "light"));
  url.searchParams.delete("wpfb_r");
  url.searchParams.set("wpfb_prototypes", item);
  url.hash = `${item}--${variant}`;
  return url.href;
}
