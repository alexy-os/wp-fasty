import * as React from "react"
import { cn } from "@/lib/utils";

function Img({ className, ...props }: React.ComponentProps<"img">) {
  return (
    <img
      data-slot="img"
      className={cn(className)}
      {...props}
    />
  )
}

Img.displayName = "Img"

function Video({ className, ...props }: React.ComponentProps<"video">) {
  return (
    <video
      data-slot="video"
      className={cn(className)}
      {...props}
    />
  )
}

Video.displayName = "Video"

function Audio({ className, ...props }: React.ComponentProps<"audio">) {
  return (
    <audio
      data-slot="audio"
      className={cn(className)}
      {...props}
    />
  )
}

Audio.displayName = "Audio"

function Picture({ className, ...props }: React.ComponentProps<"picture">) {
  return (
    <picture
      data-slot="picture"
      className={cn(className)}
      {...props}
    />
  )
}

Picture.displayName = "Picture"

function Source({ className, ...props }: React.ComponentProps<"source">) {
  return (
    <source
      data-slot="source"
      className={cn(className)}
      {...props}
    />
  )
}

Source.displayName = "Source"

function Iframe({ className, ...props }: React.ComponentProps<"iframe">) {
  return (
    <iframe
      data-slot="iframe"
      className={cn(className)}
      {...props}
    />
  )
}

Iframe.displayName = "Iframe"

function Map({ className, ...props }: React.ComponentProps<"map">) {
  return (
    <map
      data-slot="map"
      className={cn(className)}
      {...props}
    />
  )
}

Map.displayName = "Map"

function Area({ className, ...props }: React.ComponentProps<"area">) {
  return (
    <area
      data-slot="area"
      className={cn(className)}
      {...props}
    />
  )
}

Area.displayName = "Area"

function Object({ className, ...props }: React.ComponentProps<"object">) {
  return (
    <object
      data-slot="object"
      className={cn(className)}
      {...props}
    />
  )
}

Object.displayName = "Object"

function Svg({ className, ...props }: React.ComponentProps<"svg">) {
  return (
    <svg
      data-slot="svg"
      className={cn(className)}
      {...props}
    />
  )
}

Svg.displayName = "Svg"

function Canvas({ className, ...props }: React.ComponentProps<"canvas">) {
  return (
    <canvas
      data-slot="canvas"
      className={cn(className)}
      {...props}
    />
  )
}

Canvas.displayName = "Canvas"

export { Img, Video, Audio, Picture, Source, Iframe, Map, Area, Object, Svg, Canvas }