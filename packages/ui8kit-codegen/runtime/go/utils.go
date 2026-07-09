// Package uiutils holds shared Go helpers for generated UI8Kit bricks.
package uiutils

import (
	"fmt"
	"strings"

	"github.com/a-h/templ"
)

// Variants groups a base utility chain and keyed variant maps (cva-style).
type Variants struct {
	Base     string
	Keys     []string
	ByKey    map[string]map[string]string
	Defaults map[string]string
}

// Compose merges Base, variant selections, and optional tail classes.
func Compose(v Variants, selection map[string]string, extra ...string) string {
	var parts []string
	if strings.TrimSpace(v.Base) != "" {
		parts = append(parts, strings.TrimSpace(v.Base))
	}
	for _, key := range v.Keys {
		choices, ok := v.ByKey[key]
		if !ok {
			continue
		}
		choice := strings.TrimSpace(selection[key])
		if choice == "" {
			choice = strings.TrimSpace(v.Defaults[key])
		}
		if choice == "" {
			if len(choices) > 0 {
				panic(fmt.Sprintf("[Compose] missing default for key %q", key))
			}
			continue
		}
		if cls, ok2 := choices[choice]; ok2 {
			if strings.TrimSpace(cls) != "" {
				parts = append(parts, cls)
			}
		}
		// Unknown choices are silently dropped to avoid leaking typos into
		// the DOM as literal class names.
	}
	parts = append(parts, extra...)
	return Cn(parts...)
}

// Cn joins non-empty class fragments with a single space.
func Cn(classes ...string) string {
	var parts []string
	for _, c := range classes {
		if t := strings.TrimSpace(c); t != "" {
			parts = append(parts, t)
		}
	}
	return strings.Join(parts, " ")
}

// MergeAttrs merges attribute maps left to right.
func MergeAttrs(parts ...templ.Attributes) templ.Attributes {
	out := templ.Attributes{}
	for _, attrs := range parts {
		for k, v := range attrs {
			out[k] = v
		}
	}
	return out
}
