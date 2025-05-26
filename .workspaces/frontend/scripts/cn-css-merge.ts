// bun run cn-merge-dry ./src/components
// cn .workspaces/frontend && bun run cn-merge ./src/uikits/@ui8kit/src/components
import * as fs from 'fs'
import * as path from 'path'
import * as ts from 'typescript'

/**
 * Script to merge multiple string literals in cn() function calls into single strings
 * 
 * Transforms:
 * cn("class1", "class2", variable)
 * 
 * Into:
 * cn("class1 class2", variable)
 */

interface MergeOptions {
  sourceDir: string
  extensions: string[]
  dryRun: boolean
  verbose: boolean
}

class CnClassMerger {
  private options: MergeOptions

  constructor(options: MergeOptions) {
    this.options = options
  }

  /**
   * Process all files in the source directory
   */
  async processDirectory(): Promise<void> {
    const files = this.findFiles(this.options.sourceDir, this.options.extensions)

    if (this.options.verbose) {
      console.log(`Found ${files.length} files to process`)
    }

    for (const file of files) {
      await this.processFile(file)
    }
  }

  /**
   * Find all files with specified extensions
   */
  private findFiles(dir: string, extensions: string[]): string[] {
    const files: string[] = []

    const traverse = (currentDir: string) => {
      const items = fs.readdirSync(currentDir)

      for (const item of items) {
        const fullPath = path.join(currentDir, item)
        const stat = fs.statSync(fullPath)

        if (stat.isDirectory()) {
          // Skip node_modules and other common directories
          if (!['node_modules', '.git', 'dist', 'build'].includes(item)) {
            traverse(fullPath)
          }
        } else if (stat.isFile()) {
          const ext = path.extname(item)
          if (extensions.includes(ext)) {
            files.push(fullPath)
          }
        }
      }
    }

    traverse(dir)
    return files
  }

  /**
   * Process a single file
   */
  private async processFile(filePath: string): Promise<void> {
    try {
      const content = fs.readFileSync(filePath, 'utf-8')
      const sourceFile = ts.createSourceFile(
        filePath,
        content,
        ts.ScriptTarget.Latest,
        true,
        ts.ScriptKind.TSX
      )

      const transformedContent = this.transformSourceFile(sourceFile, content)

      if (transformedContent !== content) {
        if (this.options.verbose) {
          console.log(`Transformed: ${filePath}`)
        }

        if (!this.options.dryRun) {
          fs.writeFileSync(filePath, transformedContent, 'utf-8')
        }
      }
    } catch (error) {
      console.error(`Error processing ${filePath}:`, error)
    }
  }

  /**
   * Transform the source file content
   */
  private transformSourceFile(sourceFile: ts.SourceFile, originalContent: string): string {
    const changes: Array<{ start: number; end: number; replacement: string }> = []

    const visit = (node: ts.Node) => {
      // Look for call expressions
      if (ts.isCallExpression(node)) {
        // Check if it's a cn() function call
        if (this.isCnCall(node)) {
          const mergedArgs = this.mergeCnArguments(node)
          if (mergedArgs) {
            changes.push({
              start: node.arguments.pos,
              end: node.arguments.end,
              replacement: mergedArgs
            })
          }
        }
      }

      ts.forEachChild(node, visit)
    }

    visit(sourceFile)

    // Apply changes in reverse order to maintain positions
    changes.sort((a, b) => b.start - a.start)

    let result = originalContent
    for (const change of changes) {
      result = result.slice(0, change.start) + change.replacement + result.slice(change.end)
    }

    return result
  }

  /**
   * Check if a call expression is a cn() function call
   */
  private isCnCall(node: ts.CallExpression): boolean {
    if (ts.isIdentifier(node.expression)) {
      return node.expression.text === 'cn'
    }
    return false
  }

  /**
   * Merge consecutive string literal arguments in cn() call
   */
  private mergeCnArguments(node: ts.CallExpression): string | null {
    const args = Array.from(node.arguments)
    if (args.length < 2) return null

    const mergedArgs: string[] = []
    let currentStringGroup: string[] = []
    let hasChanges = false

    for (const arg of args) {
      if (ts.isStringLiteral(arg)) {
        // Extract the string content without quotes
        const stringContent = arg.text
        currentStringGroup.push(stringContent)
      } else {
        // Non-string argument, flush current string group
        if (currentStringGroup.length > 1) {
          // Merge multiple strings into one
          const mergedString = currentStringGroup.join(' ')
          mergedArgs.push(`"${mergedString}"`)
          hasChanges = true
        } else if (currentStringGroup.length === 1) {
          // Single string, keep as is
          mergedArgs.push(`"${currentStringGroup[0]}"`)
        }

        // Add the non-string argument
        const argText = arg.getFullText().trim()
        mergedArgs.push(argText)

        currentStringGroup = []
      }
    }

    // Handle remaining string group
    if (currentStringGroup.length > 1) {
      const mergedString = currentStringGroup.join(' ')
      mergedArgs.push(`"${mergedString}"`)
      hasChanges = true
    } else if (currentStringGroup.length === 1) {
      mergedArgs.push(`"${currentStringGroup[0]}"`)
    }

    if (!hasChanges) return null

    // Format the arguments with proper spacing
    return '\n' + mergedArgs.map(arg => `        ${arg}`).join(',\n') + '\n      '
  }
}

/**
 * CLI interface
 */
async function main() {
  const args = process.argv.slice(2)

  const options: MergeOptions = {
    sourceDir: args[0] || './src',
    extensions: ['.tsx', '.ts', '.jsx', '.js'],
    dryRun: args.includes('--dry-run'),
    verbose: args.includes('--verbose') || args.includes('-v')
  }

  if (args.includes('--help') || args.includes('-h')) {
    console.log(`
Usage: node cn-css-merge.ts [source-dir] [options]

Options:
  --dry-run    Show what would be changed without modifying files
  --verbose    Show detailed output
  --help       Show this help message

Examples:
  node cn-css-merge.ts ./src
  node cn-css-merge.ts ./components --dry-run --verbose
`)
    return
  }

  if (!fs.existsSync(options.sourceDir)) {
    console.error(`Source directory does not exist: ${options.sourceDir}`)
    process.exit(1)
  }

  console.log(`Processing directory: ${options.sourceDir}`)
  console.log(`Extensions: ${options.extensions.join(', ')}`)
  console.log(`Dry run: ${options.dryRun}`)
  console.log('---')

  const merger = new CnClassMerger(options)
  await merger.processDirectory()

  console.log('Done!')
}

// Run if called directly
if (require.main === module) {
  main().catch(console.error)
}

export { CnClassMerger, type MergeOptions } 