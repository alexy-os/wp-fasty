import React from 'react';
import { AvatarProps } from './interface';

/**
 * Avatar component for user profile images or initials
 * Follows the 8px design system
 */
export const Avatar: React.FC<AvatarProps> = ({
  size = "default",
  variant = "image",
  src,
  alt = "",
  initials = "",
  className = "",
}) => {
  // Limit initials to max 2 characters
  const displayInitials = initials.slice(0, 2);

  // Base64 placeholder image with muted color (gray)
  const placeholderImage = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiM2NjY2NjYiLz48L3N2Zz4=";

  return (
    <div className={`avatar ${size !== "default" ? `avatar-${size}` : ""} ${className}`}>
      {variant === "image" ? (
        <img
          src={src || placeholderImage}
          alt={alt}
          className="avatar-image w-full h-full object-cover"
          onError={(e) => {
            // On image error, show placeholder
            e.currentTarget.src = placeholderImage;
          }}
        />
      ) : (
        <span className="avatar-initials font-medium">
          {displayInitials}
        </span>
      )}
    </div>
  );
}; 