/**
 * Emits `cmd/parity/main.go` — a JSON-in/JSON-out harness that renders any
 * generated Templ part from canonical (PascalCase) props. Parity tests use
 * it to assert the Templ runtime against the canonical renderer, closing the
 * loop across all four runtimes.
 */

import { fileStem, type BrickDef } from "../domain/model";
import { BANNER, type GeneratedFile } from "./common";

export function emitGoParityHarness(bricks: BrickDef[], goModule: string): GeneratedFile {
  const imports = new Map<string, string>(); // alias -> import path
  for (const brick of bricks) {
    const alias = (brick.goPackage ?? brick.dir).replace(/-/g, "");
    imports.set(alias, `${goModule}/ui/${brick.dir}`);
  }

  const cases: string[] = [];
  for (const brick of bricks) {
    const alias = (brick.goPackage ?? brick.dir).replace(/-/g, "");
    for (const part of brick.parts) {
      const key = `${brick.dir}/${fileStem(brick)}/${part.name}`;
      const hasChildren = part.props.some((p) => p.type === "children");
      const renderCall = hasChildren
        ? `${alias}.${part.name}(p).Render(templ.WithChildren(ctx, children), buf)`
        : `${alias}.${part.name}(p).Render(ctx, buf)`;
      cases.push(
        `\tcase ${JSON.stringify(key)}:\n` +
          `\t\tvar p ${alias}.${part.name}Props\n` +
          `\t\tif err := json.Unmarshal(propsJSON, &p); err != nil {\n` +
          `\t\t\treturn err\n` +
          `\t\t}\n` +
          `\t\treturn ${renderCall}`
      );
    }
  }

  const importLines = [...imports.entries()]
    .sort(([, a], [, b]) => (a < b ? -1 : 1))
    .map(([alias, path]) => {
      const last = path.split("/").pop()!;
      return last === alias ? `\t"${path}"` : `\t${alias} "${path}"`;
    });

  const contents = `// ${BANNER}

// Command parity renders any generated part from canonical JSON props.
// stdin:  [{"name":"button/button/Button","props":{...},"children":"<b>x</b>"}]
// stdout: ["<button ...>...</button>", ...]
package main

import (
	"bufio"
	"context"
	"encoding/json"
	"fmt"
	"os"
	"strings"

	"github.com/a-h/templ"

${importLines.join("\n")}
)

type renderRequest struct {
	Name     string          \`json:"name"\`
	Props    json.RawMessage \`json:"props"\`
	Children string          \`json:"children"\`
}

func renderCase(ctx context.Context, name string, propsJSON []byte, children templ.Component, buf *strings.Builder) error {
	switch name {
${cases.join("\n")}
	}
	return fmt.Errorf("unknown case %q", name)
}

func main() {
	var requests []renderRequest
	if err := json.NewDecoder(bufio.NewReader(os.Stdin)).Decode(&requests); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
	results := make([]string, 0, len(requests))
	for _, req := range requests {
		var buf strings.Builder
		children := templ.Raw(req.Children)
		if err := renderCase(context.Background(), req.Name, req.Props, children, &buf); err != nil {
			fmt.Fprintf(os.Stderr, "%s: %v\\n", req.Name, err)
			os.Exit(1)
		}
		results = append(results, buf.String())
	}
	if err := json.NewEncoder(os.Stdout).Encode(results); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
}
`;
  return { path: "cmd/parity/main.go", contents };
}

export function emitGoMod(goModule: string): GeneratedFile {
  return {
    path: "go.mod",
    contents: `module ${goModule}\n\ngo 1.23.0\n\nrequire github.com/a-h/templ v0.3.1001\n`,
  };
}
