// The slug of a new page (the "New page" panel): made from the title until the slug field is edited, the way
// WordPress' sanitize_title() makes one — lowercase, accents of Latin letters dropped (remove_accents), anything but
// letters and digits becomes one "-".
// Letters of any script stay (WordPress stores them URL-encoded); the server sanitizes and makes it unique again.

export const SLUG_MAX = 200;

export function slugify(title: string): string {
  return Array.from(title, latin)
    .join("")
    .toLowerCase()
    .replace(/['’]+/g, "")
    .replace(/[^\p{L}\p{N}]+/gu, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, SLUG_MAX)
    .replace(/-+$/, "");
}

/** what the slug field shows: the title's slug while `edited` is false, the typed value once edited */
export function slugOf(title: string, typed: string, edited: boolean): string {
  return edited ? typed : slugify(title);
}

/** a Latin letter without its accents (é → e, ß stays); letters of other scripts unchanged (й stays й) */
function latin(c: string): string {
  const d = c.normalize("NFKD");
  return /^[A-Za-z]\p{M}+$/u.test(d) ? d[0]! : c;
}
