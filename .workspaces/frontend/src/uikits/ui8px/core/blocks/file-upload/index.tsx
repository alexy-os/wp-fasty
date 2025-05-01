import React, { useRef, useState, useCallback } from 'react';
import { FileUploadProps, FileItem } from './interface';

/**
 * Helper to format file size
 */
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

/**
 * File Upload component
 * Follows the 8px design system
 */
export const FileUpload: React.FC<FileUploadProps> = ({
  variant = "default",
  size = "default",
  label = "Drop files here or click to upload",
  description,
  accept,
  maxSize,
  multiple = false,
  files = [],
  onFilesChange,
  disabled = false,
  className = "",
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  const handleDragOver = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    if (!disabled) setIsDragging(true);
  }, [disabled]);

  const handleDragLeave = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  }, []);

  const handleDrop = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();

    setIsDragging(false);

    if (disabled) return;

    const droppedFiles = Array.from(e.dataTransfer.files);
    handleFiles(droppedFiles);
  }, [disabled]);

  const handleFileInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const selectedFiles = Array.from(e.target.files);
      handleFiles(selectedFiles);
      // Reset input so the same file can be selected again if removed
      e.target.value = '';
    }
  }, []);

  const handleFiles = useCallback((newFiles: File[]) => {
    if (!onFilesChange) return;

    const validFiles = newFiles.map(file => {
      // Check file size
      const isValidSize = !maxSize || file.size <= maxSize;

      // Generate file item
      const fileItem: FileItem = {
        id: `file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        name: file.name,
        size: file.size,
        type: file.type,
        progress: 100, // Default to 100 for this example
        uploaded: isValidSize,
        error: !isValidSize ? `File is too large. Maximum size is ${formatFileSize(maxSize!)}` : undefined
      };

      return fileItem;
    });

    // If multiple is true, append new files, otherwise replace
    const updatedFiles = multiple ? [...files, ...validFiles] : validFiles;
    onFilesChange(updatedFiles);
  }, [files, maxSize, multiple, onFilesChange]);

  const handleClick = useCallback(() => {
    if (!disabled && inputRef.current) {
      inputRef.current.click();
    }
  }, [disabled]);

  const handleRemoveFile = useCallback((id: string) => {
    if (!onFilesChange) return;

    const updatedFiles = files.filter(file => file.id !== id);
    onFilesChange(updatedFiles);
  }, [files, onFilesChange]);

  return (
    <div className={`file-upload ${className}`}>
      {/* Upload area */}
      <div
        className={`file-upload-dropzone file-upload-${variant} file-upload-${size} ${isDragging ? 'file-upload-dragging' : ''} ${disabled ? 'file-upload-disabled opacity-60 pointer-events-none' : ''}`}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={handleClick}
      >
        {/* Icon placeholder - would be replaced with proper icon in real implementation */}
        <div className="file-upload-icon w-12 h-12 rounded-full bg-muted/60 flex items-center justify-center mb-4">
          <span className="text-muted-foreground">+</span>
        </div>

        <div className="text-center">
          <div className="file-upload-label text-base font-medium mb-1">
            {label}
          </div>
          {description && (
            <div className="file-upload-description text-sm text-muted-foreground">
              {description}
            </div>
          )}
        </div>

        <input
          ref={inputRef}
          type="file"
          className="sr-only"
          accept={accept}
          multiple={multiple}
          onChange={handleFileInputChange}
          disabled={disabled}
        />
      </div>

      {/* File list */}
      {files.length > 0 && (
        <ul className="file-upload-list mt-4 space-y-2">
          {files.map(file => (
            <li
              key={file.id}
              className={`file-item ${file.error ? 'file-item-error' : file.uploaded ? 'file-item-success' : 'file-item-uploading'}`}
            >
              <div className="flex items-center space-x-3">
                {/* Icon placeholder - would be replaced with proper icon in real implementation */}
                <div className="file-item-icon w-8 h-8 rounded bg-muted/40 flex items-center justify-center">
                  <span className="text-xs text-muted-foreground">
                    {file.type.split('/')[0].substring(0, 1).toUpperCase()}
                  </span>
                </div>

                <div>
                  <div className="file-item-name text-sm font-medium truncate max-w-xs">
                    {file.name}
                  </div>
                  <div className="file-item-size text-xs text-muted-foreground">
                    {formatFileSize(file.size)}
                  </div>
                </div>
              </div>

              <div className="flex items-center">
                {/* Show error message if any */}
                {file.error && (
                  <span className="text-xs text-destructive mr-2">
                    {file.error}
                  </span>
                )}

                {/* Show progress if uploading */}
                {!file.error && !file.uploaded && file.progress !== undefined && (
                  <div className="file-item-progress w-24 h-1.5 bg-muted rounded-full overflow-hidden mr-2">
                    <div
                      className="h-full bg-primary"
                      style={{ width: `${file.progress}%` }}
                    />
                  </div>
                )}

                {/* Remove button */}
                <button
                  type="button"
                  className="file-item-remove-button p-1 text-muted-foreground hover:text-foreground"
                  onClick={() => handleRemoveFile(file.id)}
                  aria-label="Remove file"
                >
                  {/* Placeholder for remove icon */}
                  <span className="text-xs">×</span>
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}; 