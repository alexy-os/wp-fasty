import { CnClassMerger } from '../cn-css-merge'

/**
 * Utility functions for running cn-css-merge with different configurations
 */

export async function mergeComponentClasses(componentDir: string, dryRun = false) {
  const merger = new CnClassMerger({
    sourceDir: componentDir,
    extensions: ['.tsx', '.ts'],
    dryRun,
    verbose: true
  })

  await merger.processDirectory()
}

export async function mergeAllUIKitClasses(uikitDir: string, dryRun = false) {
  const merger = new CnClassMerger({
    sourceDir: uikitDir,
    extensions: ['.tsx', '.ts', '.jsx', '.js'],
    dryRun,
    verbose: true
  })

  await merger.processDirectory()
}

// Example usage for your nav component
export async function mergeNavComponent() {
  await mergeComponentClasses('.workspaces/frontend/src/uikits/@ui8kit/src/components', true)
} 