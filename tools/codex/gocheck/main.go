// Cross-check of the WordPress Codex bytes against the Go reference (fastygo/codex v0.4.2):
//
//	go run . <golden dir> <codex.manifest.json>
//
// For every body of tests/fixtures/codex-draft/golden/<site> (what /wpfasty/v1/codex served): entry bodies and list
// items must be fixed points of canonical.Canonicalize (codex-entry/v1), term items of canonical.CanonicalJSON, the
// Codex-Digest is recomputed, and entries + terms + settings (whole, incl. the contact block contact, hours,
// hours_exceptions, map) go through validate.CollectionV04 with the manifest.
// Exit 1 on any difference. The site origin placeholder {base} is replaced by a fixed origin.
package main

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"

	"github.com/fastygo/codex/canonical"
	"github.com/fastygo/codex/schema"
	"github.com/fastygo/codex/validate"
	"github.com/fastygo/codex/validation"
)

func main() {
	if len(os.Args) != 3 {
		fmt.Fprintln(os.Stderr, "usage: gocheck <golden dir> <codex.manifest.json>")
		os.Exit(2)
	}
	dir, manifestFile := os.Args[1], os.Args[2]
	files, _ := filepath.Glob(filepath.Join(dir, "*.json"))
	sort.Strings(files)
	fails := 0
	fail := func(format string, args ...any) { fails++; fmt.Printf("✗ "+format+"\n", args...) }
	var coll validate.Collection
	bodies := 0
	for _, f := range files {
		raw, err := os.ReadFile(f)
		if err != nil {
			fail("%s: %v", f, err)
			continue
		}
		text := strings.ReplaceAll(string(raw), "{base}", "http://codex.test")
		name := filepath.Base(f)
		switch {
		case strings.HasPrefix(name, "entry."):
			bodies++
			got, err := canonical.Canonicalize(text)
			if err != nil {
				fail("%s: Canonicalize: %s %v", name, canonical.Code(err), err)
			} else if got != text {
				fail("%s: not canonical\n  php: %s\n  go:  %s", name, text, got)
			}
			if _, err := canonical.Digest(text); err != nil {
				fail("%s: Digest: %v", name, err)
			}
			var v any
			_ = json.Unmarshal([]byte(text), &v)
			coll.Entries = append(coll.Entries, validate.RecordFile{File: name, Data: v})
		case strings.HasPrefix(name, "entries."), strings.HasPrefix(name, "terms."):
			var doc struct {
				Data []json.RawMessage `json:"data"`
			}
			if err := json.Unmarshal([]byte(text), &doc); err != nil {
				fail("%s: %v", name, err)
				continue
			}
			for i, item := range doc.Data {
				bodies++
				canon := canonical.Canonicalize
				if strings.HasPrefix(name, "terms.") {
					canon = canonical.CanonicalJSON
				}
				got, err := canon(string(item))
				if err != nil {
					fail("%s[%d]: %v", name, i, err)
				} else if got != string(item) {
					fail("%s[%d]: not canonical\n  php: %s\n  go:  %s", name, i, item, got)
				}
				if strings.HasPrefix(name, "terms.") {
					var v any
					_ = json.Unmarshal(item, &v)
					coll.Terms = append(coll.Terms, validate.RecordFile{File: fmt.Sprintf("%s[%d]", name, i), Data: v})
				}
			}
		case name == "settings.json":
			bodies++
			if got, err := canonical.CanonicalJSON(text); err != nil || got != text {
				fail("%s: not JCS (%v)\n  php: %s\n  go:  %s", name, err, text, got)
			}
			var v any
			_ = json.Unmarshal([]byte(text), &v)
			coll.Settings = append(coll.Settings, validate.RecordFile{File: name, Data: v})
		}
	}
	var manifest schema.Manifest
	if raw, err := os.ReadFile(manifestFile); err != nil {
		fail("%s: %v", manifestFile, err)
	} else if err := json.Unmarshal(raw, &manifest); err != nil {
		fail("%s: %v", manifestFile, err)
	}
	errs := 0
	for _, issue := range validation.ErrorsOnly(validate.CollectionV04(coll, validate.Options{Manifest: &manifest})) {
		errs++
		fail("CollectionV04: %s %s %s", issue.Code, issue.Path, issue.Message)
	}
	fmt.Printf("%s: %d bodies vs fastygo/codex canonical, %d entries + %d terms + %d settings through CollectionV04, %d error(s)\n",
		dir, bodies, len(coll.Entries), len(coll.Terms), len(coll.Settings), errs)
	if fails > 0 {
		os.Exit(1)
	}
}
