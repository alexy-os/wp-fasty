"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// Avatar context for image loading status
const AvatarContext = React.createContext<{
  imageStatus: "idle" | "loading" | "loaded" | "error";
  setImageStatus: (status: "idle" | "loading" | "loaded" | "error") => void;
} | null>(null);

function Avatar({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  // Manage image loading status in context
  const [imageStatus, setImageStatus] = React.useState<"idle" | "loading" | "loaded" | "error">("idle");
  return (
    <AvatarContext.Provider value={{ imageStatus, setImageStatus }}>
      <span

        className={cn(
          "relative flex size-8 shrink-0 overflow-hidden rounded-full",
          className
        )}
        {...props} />

    </AvatarContext.Provider>);

}

interface AvatarImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {}

function AvatarImage({ className, src, ...props }: AvatarImageProps) {
  const ctx = React.useContext(AvatarContext);
  const [status, setStatus] = React.useState<"idle" | "loading" | "loaded" | "error">("idle");

  React.useEffect(() => {
    if (!src) {
      setStatus("error");
      ctx?.setImageStatus("error");
      return;
    }
    setStatus("loading");
    ctx?.setImageStatus("loading");
    const img = new window.Image();
    img.src = src;
    img.onload = () => {
      setStatus("loaded");
      ctx?.setImageStatus("loaded");
    };
    img.onerror = () => {
      setStatus("error");
      ctx?.setImageStatus("error");
    };
    // Clean up
    return () => {
      img.onload = null;
      img.onerror = null;
    };
  }, [src]);

  if (status !== "loaded") return null;
  return (
    <img

      className={cn("aspect-square size-full", className)}
      src={src}
      {...props} />);


}

interface AvatarFallbackProps extends React.HTMLAttributes<HTMLSpanElement> {
  delayMs?: number;
}

function AvatarFallback({ className, delayMs, ...props }: AvatarFallbackProps) {
  const ctx = React.useContext(AvatarContext);
  const [canRender, setCanRender] = React.useState(delayMs === undefined);

  React.useEffect(() => {
    if (delayMs !== undefined) {
      const timer = setTimeout(() => setCanRender(true), delayMs);
      return () => clearTimeout(timer);
    }
  }, [delayMs]);

  if (!canRender) return null;
  if (ctx?.imageStatus === "loaded") return null;
  return (
    <span

      className={cn(
        "bg-muted flex size-full items-center justify-center rounded-full",
        className
      )}
      {...props} />);


}

export { Avatar, AvatarImage, AvatarFallback };