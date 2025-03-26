# Utils

<!-- @doc-source: Utils -->


## Methods

### pathToUrl
<!-- @doc-source: Utils.pathToUrl -->
Utility class with helper methods
/
final class Utils
{
/**
Default allowed HTML tags for sanitization

#### Parameters

- ``: path string

#### Returns



### getFileVersion
<!-- @doc-source: Utils.getFileVersion -->
Get file version based on modification time

#### Parameters

- ``: string $file File path
- ``: file string

#### Returns



### fileExistsUri
<!-- @doc-source: Utils.fileExistsUri -->
Check if a file exists and return appropriate URI

#### Parameters

- ``: string $uri URI to check
- ``: string $default Default URI if file not found
- ``: uri string
- ``: default string

#### Returns



### sanitizeHtml
<!-- @doc-source: Utils.sanitizeHtml -->
Sanitize HTML content with allowed tags

#### Parameters

- ``: string $content Content to sanitize
- ``: array $allowedTags Allowed HTML tags
- ``: content string
- ``: allowedTags array

#### Returns



### isDebugEnabled
<!-- @doc-source: Utils.isDebugEnabled -->
Check if debug mode is enabled

#### Returns



### isDevelopmentEnvironment
<!-- @doc-source: Utils.isDevelopmentEnvironment -->
Check if the current environment is development

#### Returns



### getFrameworkVersion
<!-- @doc-source: Utils.getFrameworkVersion -->
Get the current framework version

#### Returns



### compareVersions
<!-- @doc-source: Utils.compareVersions -->
Compare framework versions

#### Parameters

- ``: string $version1 First version
- ``: string $version2 Second version
- ``: version1 string
- ``: version2 string

#### Returns



### isVersionAtLeast
<!-- @doc-source: Utils.isVersionAtLeast -->
Check if current framework version is at least the given version

#### Parameters

- ``: string $version Version to check against
- ``: version string

#### Returns



