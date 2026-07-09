package uiutils

// HeadingLevel maps to h1–h6 (default h2 when zero or out of range).
type HeadingLevel int

const (
	H1 HeadingLevel = 1
	H2 HeadingLevel = 2
	H3 HeadingLevel = 3
	H4 HeadingLevel = 4
	H5 HeadingLevel = 5
	H6 HeadingLevel = 6
)

// TitleTag returns h1-h6 from order.
func TitleTag(order HeadingLevel) string {
	switch int(order) {
	case 1:
		return "h1"
	case 3:
		return "h3"
	case 4:
		return "h4"
	case 5:
		return "h5"
	case 6:
		return "h6"
	default:
		return "h2"
	}
}
