package uiutils

import "strings"

// Tiny attribute-logic helpers used by generated components. One
// implementation per runtime family keeps every default-resolution decision
// table identical across Templ, React, Svelte, and Vue (mirrors expr.ts).

// TrimStr trims surrounding whitespace.
func TrimStr(v string) string { return strings.TrimSpace(v) }

// IsSetStr reports whether the trimmed string is non-empty.
func IsSetStr(v string) bool { return strings.TrimSpace(v) != "" }

// DefaultIfEmpty returns fallback when the trimmed value is empty.
func DefaultIfEmpty(v, fallback string) string {
	t := strings.TrimSpace(v)
	if t == "" {
		return fallback
	}
	return t
}

// OneOfOr returns the trimmed value when in allowed, otherwise fallback.
func OneOfOr(v, fallback string, allowed ...string) string {
	t := strings.TrimSpace(v)
	for _, a := range allowed {
		if t == a {
			return t
		}
	}
	return fallback
}

// OneOfOrLower lowercases before the whitelist check.
func OneOfOrLower(v, fallback string, allowed ...string) string {
	return OneOfOr(strings.ToLower(strings.TrimSpace(v)), fallback, allowed...)
}

// MapOr is a decision table: mapping[trim(v)] or fallback.
func MapOr(v string, mapping map[string]string, fallback string) string {
	if out, ok := mapping[strings.TrimSpace(v)]; ok {
		return out
	}
	return fallback
}

// MapOrLower lowercases before the table lookup.
func MapOrLower(v string, mapping map[string]string, fallback string) string {
	return MapOr(strings.ToLower(strings.TrimSpace(v)), mapping, fallback)
}

// IntMapOr is an integer decision table: mapping[v] or fallback.
func IntMapOr(v int, mapping map[int]string, fallback string) string {
	if out, ok := mapping[v]; ok {
		return out
	}
	return fallback
}

// BoolStr stringifies ARIA state: always "true"/"false", never omitted.
func BoolStr(v bool) string {
	if v {
		return "true"
	}
	return "false"
}

// ConcatTrim concatenates trimmed parts (icon Prefix + Name).
func ConcatTrim(parts ...string) string {
	var b strings.Builder
	for _, p := range parts {
		b.WriteString(strings.TrimSpace(p))
	}
	return b.String()
}

// If is an eager ternary for value selection in attribute expressions.
func If[T any](cond bool, then, els T) T {
	if cond {
		return then
	}
	return els
}
