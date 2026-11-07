/** Component-owned immutable selection. No persistence: constructing a new editor follows its interface. */
export type PreviewMode = "interface" | "light" | "dark";
export const previewChoices = [
  { mode: "interface", label: "Как в интерфейсе" },
  { mode: "light", label: "Light" },
  { mode: "dark", label: "Dark" },
] as const;
export const previewAppearanceLabel = "Preview appearance";

export class PreviewAppearance {
  constructor(readonly mode: PreviewMode = "interface") {
    if (!previewChoices.some(choice => choice.mode === mode)) throw new TypeError("Invalid preview appearance");
  }
  scheme(interfaceDark: boolean): "light" | "dark" {
    return this.mode === "interface" ? (interfaceDark ? "dark" : "light") : this.mode;
  }
}
