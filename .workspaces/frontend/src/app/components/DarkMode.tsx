import { Button } from "./Button";

export function DarkMode() {
  return (
    <Button
      id="darkmode"
      variant="ghost"
      size="sm"
      className={`px-2 !rounded-full`}
      aria-label="Toggle dark mode"
    >
      <span className="latty latty-sun sun-icon"></span>
      <span className="latty latty-moon moon-icon"></span>
    </Button>
  );
}
