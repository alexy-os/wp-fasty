import { Button } from "./Button";
import { Sun, Moon } from "lucide-react";

export function DarkMode() {
  return (
    <Button
      id="darkmode"
      variant="ghost"
      size="sm"
      className={`!rounded-full`}
      aria-label="Toggle dark mode"
    >
      <Sun className="w-4 h-4 sun-icon" />
      <Moon className="w-4 h-4 moon-icon" />
    </Button>
  );
}
