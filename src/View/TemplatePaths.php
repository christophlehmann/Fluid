<?php
namespace TYPO3Fluid\Fluid\View;

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Template Paths Holder
 *
 * Class used to hold and resolve template files
 * and paths in multiple supported ways.
 *
 * The purpose of this class is to homogenise the
 * API that is used when working with template
 * paths coming from TypoScript, as well as serve
 * as a way to quickly generate default template-,
 * layout- and partial root paths by package.
 *
 * The constructor accepts two different types of
 * input - anything not of those types is silently
 * ignored:
 *
 * - a `string` input is assumed a package name
 *   and will call the `fillDefaultsByPackageName`
 *   value filling method.
 * - an `array` input is assumed a TypoScript-style
 *   array of root paths in one or more of the
 *   supported structures and will call the
 *   `fillFromTypoScriptArray` method.
 *
 * Either method can also be called after instance
 * is created, but both will overwrite any paths
 * you have previously configured.
 */
class TemplatePaths {

	const DEFAULT_FORMAT = 'html';
	const DEFAULT_TEMPLATES_DIRECTORY = 'Resources/Private/Templates/';
	const DEFAULT_LAYOUTS_DIRECTORY = 'Resources/Private/Layouts/';
	const DEFAULT_PARTIALS_DIRECTORY = 'Resources/Private/Partials/';
	const CONFIG_TEMPLATEROOTPATHS = 'templateRootPaths';
	const CONFIG_LAYOUTROOTPATHS = 'layoutRootPaths';
	const CONFIG_PARTIALROOTPATHS = 'partialRootPaths';
	const CONFIG_FORMAT = 'format';

	/**
	 * Holds already resolved identifiers for template files
	 *
	 * @var array
	 */
	protected static $resolvedIdentifiers = array(
		'templates' => array(),
		'layouts' => array(),
		'partials' => array()
	);

	/**
	 * Holds already resolved identifiers for template files
	 *
	 * @var array
	 */
	protected static $resolvedFiles = array(
		'templates' => array(),
		'layouts' => array(),
		'partials' => array()
	);

	/**
	 * @var array
	 */
	protected $templateRootPaths = array();

	/**
	 * @var array
	 */
	protected $layoutRootPaths = array();

	/**
	 * @var array
	 */
	protected $partialRootPaths = array();

	/**
	 * @var string
	 */
	protected $templatePathAndFilename = NULL;

	/**
	 * @var string
	 */
	protected $layoutPathAndFilename = NULL;

	/**
	 * @var string|NULL
	 */
	protected $templateSource = NULL;

	/**
	 * @var string
	 */
	protected $format = self::DEFAULT_FORMAT;

	/**
	 * @param string|NULL $packageNameOrArray
	 */
	public function __construct($packageNameOrArray = NULL) {
		if (is_array($packageNameOrArray)) {
			$this->fillFromConfigurationArray($packageNameOrArray);
		} elseif (!empty($packageNameOrArray)) {
			$this->fillDefaultsByPackageName($packageNameOrArray);
		}
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return array(
			self::CONFIG_TEMPLATEROOTPATHS => $this->sanitizePaths($this->getTemplateRootPaths()),
			self::CONFIG_LAYOUTROOTPATHS => $this->sanitizePaths($this->getLayoutRootPaths()),
			self::CONFIG_PARTIALROOTPATHS => $this->sanitizePaths($this->getPartialRootPaths())
		);
	}

	/**
	 * @param string $templatePathAndFilename
	 * @return void
	 */
	public function setTemplatePathAndFilename($templatePathAndFilename) {
		$this->templatePathAndFilename = $this->sanitizePath($templatePathAndFilename);
	}

	/**
	 * @param string $layoutPathAndFilename
	 * @return void
	 */
	public function setLayoutPathAndFilename($layoutPathAndFilename) {
		$this->layoutPathAndFilename = $layoutPathAndFilename;
	}

	/**
	 * @return array
	 */
	public function getTemplateRootPaths() {
		return $this->templateRootPaths;
	}

	/**
	 * @param array $templateRootPaths
	 * @return void
	 */
	public function setTemplateRootPaths(array $templateRootPaths) {
		$this->templateRootPaths = $this->sanitizePaths($templateRootPaths);
		$this->clearResolvedIdentifiersAndTemplates();
	}

	/**
	 * @return array
	 */
	public function getLayoutRootPaths() {
		return $this->layoutRootPaths;
	}

	/**
	 * @param array $layoutRootPaths
	 * @return void
	 */
	public function setLayoutRootPaths(array $layoutRootPaths) {
		$this->layoutRootPaths = $this->sanitizePaths($layoutRootPaths);
		$this->clearResolvedIdentifiersAndTemplates();
	}

	/**
	 * @return array
	 */
	public function getPartialRootPaths() {
		return $this->partialRootPaths;
	}

	/**
	 * @param array $partialRootPaths
	 * @return void
	 */
	public function setPartialRootPaths(array $partialRootPaths) {
		$this->partialRootPaths = $this->sanitizePaths($partialRootPaths);
		$this->clearResolvedIdentifiersAndTemplates();
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * @param string $format
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Attempts to resolve an absolute filename
	 * of a template (i.e. `templateRootPaths`)
	 * using a controller name, action and format.
	 *
	 * Works _backwards_ through template paths in
	 * order to achieve an "overlay"-type behavior
	 * where the last paths added are the first to
	 * be checked and the first path added acts as
	 * fallback if no other paths have the file.
	 *
	 * If the file does not exist in any path,
	 * including fallback path, `NULL` is returned.
	 *
	 * Path configurations filled from TypoScript
	 * is automatically recorded in the right
	 * order (see `fillFromTypoScriptArray`), but
	 * when manually setting the paths that should
	 * be checked, you as user must be aware of
	 * this reverse behavior (which you should
	 * already be, given that it is the same way
	 * TypoScript path configurations work).
	 *
	 * @param string $controller
	 * @param string $action
	 * @param string $format
	 * @return string|NULL
	 * @api
	 */
	public function resolveTemplateFileForControllerAndActionAndFormat($controller, $action, $format = self::DEFAULT_FORMAT) {
		if ($this->templatePathAndFilename !== NULL) {
			return $this->templatePathAndFilename;
		}
		$controller = str_replace('\\', '/', $controller);
		$action = ucfirst($action);
		$identifier = $controller . '/' . $action . '.' . $format;
		if (!array_key_exists($identifier, self::$resolvedFiles['templates'])) {
			foreach ($this->getTemplateRootPaths() as $templateRootPath) {
				$candidate = $this->ensureAbsolutePath($templateRootPath . $action . '.' . $format);
				if (file_exists($candidate)) {
					return self::$resolvedFiles['templates'][$identifier] = $candidate;
				}
			}
			foreach ($this->getTemplateRootPaths() as $templateRootPath) {
				$candidate = $this->ensureAbsolutePath($templateRootPath . $identifier);
				if (file_exists($candidate)) {
					return self::$resolvedFiles['templates'][$identifier] = $candidate;
				}
			}
		}
		return isset(self::$resolvedFiles['templates'][$identifier]) ? self::$resolvedFiles['templates'][$identifier] : NULL;
	}

	/**
	 * @param string $controllerName
	 * @param string $format
	 * @return array
	 */
	public function resolveAvailableTemplateFiles($controllerName, $format = self::DEFAULT_FORMAT) {
		$paths = $this->getTemplateRootPaths();
		foreach ($paths as $index => $path) {
			$paths[$index] = $path . $controllerName . '/';
		}
		return $this->resolveFilesInFolders($paths, $format);
	}

	/**
	 * @param string $format
	 * @return array
	 */
	public function resolveAvailablePartialFiles($format = self::DEFAULT_FORMAT) {
		return $this->resolveFilesInFolders($this->getPartialRootPaths(), $format);
	}

	/**
	 * @param string $format
	 * @return array
	 */
	public function resolveAvailableLayoutFiles($format = self::DEFAULT_FORMAT) {
		return $this->resolveFilesInFolders($this->getLayoutRootPaths(), $format);
	}

	/**
	 * @param array $folders
	 * @param string $format
	 * @return array
	 */
	protected function resolveFilesInFolders(array $folders, $format) {
		$files = array();
		foreach ($folders as $folder) {
			$files = array_merge($files, $this->resolveFilesInFolder($folder, $format));
		}
		return array_values($files);
	}

	/**
	 * @param string $folder
	 * @param string $format
	 * @return array
	 */
	protected function resolveFilesInFolder($folder, $format) {
		$files = glob($folder . '*.' . $format);
		return !$files ? array() : $files;
	}

	/**
	 * Fills path arrays based on a traditional
	 * TypoScript array which may contain one or
	 * more of the supported structures, in order
	 * of priority:
	 *
	 * - `plugin.tx_yourext.view.templateRootPath` and siblings.
	 * - `plugin.tx_yourext.view.templateRootPaths` and siblings.
	 * - `plugin.tx_yourext.view.overlays.otherextension.templateRootPath` and siblings.
	 *
	 * The paths are treated as follows, using the
	 * `template`-type paths as an example:
	 *
	 * - If `templateRootPath` is defined, it gets
	 *   used as the _first_ path in the internal
	 *   paths array.
	 * - If `templateRootPaths` is defined, all
	 *   values from it are _appended_ to the
	 *   internal paths array.
	 * - If `overlays.*` exists in the array it is
	 *   iterated, each `templateRootPath` entry
	 *   from it _appended_ to the internal array.
	 *
	 * The result is that after filling, the path
	 * arrays will contain one or more entries in
	 * the order described above, depending on how
	 * many of the possible configurations were
	 * present in the input array.
	 *
	 * Will replace any currently configured paths.
	 *
	 * @param array $paths
	 * @return void
	 * @api
	 */
	public function fillFromConfigurationArray(array $paths) {
		$this->clearResolvedIdentifiersAndTemplates();
		list ($templateRootPaths, $layoutRootPaths, $partialRootPaths, $format) = $this->extractPathArrays($paths);
		$this->setTemplateRootPaths($templateRootPaths);
		$this->setLayoutRootPaths($layoutRootPaths);
		$this->setPartialRootPaths($partialRootPaths);
		$this->setFormat($format);
	}

	/**
	 * Fills path arrays with default expected paths
	 * based on package name (converted to extension
	 * key automatically).
	 *
	 * Will replace any currently configured paths.
	 *
	 * @param string $packageName
	 * @return void
	 * @api
	 */
	public function fillDefaultsByPackageName($packageName) {
		$this->clearResolvedIdentifiersAndTemplates();
		$path = $this->getPackagePath($packageName);
		$this->setTemplateRootPaths(array($path . self::DEFAULT_TEMPLATES_DIRECTORY));
		$this->setLayoutRootPaths(array($path . self::DEFAULT_LAYOUTS_DIRECTORY));
		$this->setPartialRootPaths(array($path . self::DEFAULT_PARTIALS_DIRECTORY));
	}

	/**
	 * Sanitize a path, ensuring it is absolute and
	 * if a directory, suffixed by a trailing slash.
	 *
	 * @param string|array $path
	 * @return string
	 */
	protected function sanitizePath($path) {
		if (!empty($path)) {
			$path = str_replace(array('\\', '//'), '/', $path);
			$path = $this->ensureAbsolutePath($path);
			if (is_dir($path)) {
				$path = $this->ensureSuffixedPath($path);
			}
		}
		return $path;
	}

	/**
	 * Sanitize paths passing each through sanitizePath().
	 *
	 * @param array $paths
	 * @return array
	 */
	protected function sanitizePaths(array $paths) {
		return array_unique(array_map(array($this, 'sanitizePath'), $paths));
	}

	/**
	 * Guarantees that $reference is turned into a
	 * correct, absolute path.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function ensureAbsolutePath($path) {
		return ((!empty($path) && $path{0} !== '/' && $path{1} !== ':') ? realpath($path) : $path);
	}

	/**
	 * Guarantees that array $reference with paths
	 * are turned into correct, absolute paths
	 *
	 * @param array $reference
	 * @return array
	 */
	protected function ensureAbsolutePaths(array $reference) {
		return array_map(array($this, 'ensureAbsolutePath'), $reference);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function ensureSuffixedPath($path) {
		return rtrim($path, '/') . '/';
	}

	/**
	 * Extract an array of three arrays of paths, one
	 * for each of the types of Fluid file resources.
	 * Accepts one or both of the singular and plural
	 * path definitions in the input - returns the
	 * combined collections of paths based on both
	 * the singular and plural entries with the singular
	 * entries being recorded first and plurals second.
	 *
	 * Sorts the passed paths by index in array, in
	 * reverse, so that the base View class will iterate
	 * the array in the right order when resolving files.
	 *
	 * Adds legacy singular name as last option, if set.
	 *
	 * @param array $paths
	 * @return array
	 */
	protected function extractPathArrays(array $paths) {
		$format = self::DEFAULT_FORMAT;
		// pre-processing: if special parameters exist, extract them:
		if (isset($paths[self::CONFIG_FORMAT])) {
			$format = $paths[self::CONFIG_FORMAT];
		}
		$pathParts = array(
			self::CONFIG_TEMPLATEROOTPATHS,
			self::CONFIG_LAYOUTROOTPATHS,
			self::CONFIG_PARTIALROOTPATHS
		);
		$pathCollections = array();
		foreach ($pathParts as $pathPart) {
			$partPaths = array();
			if (isset($paths[$pathPart]) && is_array($paths[$pathPart])) {
				krsort($paths[$pathPart], SORT_NUMERIC);
				$partPaths = array_merge($partPaths, array_values($paths[$pathPart]));
			}
			$pathCollections[] = array_values(array_unique(array_map(array($this, 'ensureSuffixedPath'), $partPaths)));
		}
		$pathCollections = array_map(array($this, 'ensureAbsolutePaths'), $pathCollections);
		$pathCollections[] = $format;
		return $pathCollections;
	}

	/**
	 * @param string $packageName
	 * @return string
	 */
	protected function getPackagePath($packageName) {
		return '';
	}

	/**
	 * Returns a unique identifier for the resolved layout file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $layoutName The name of the layout
	 * @return string layout identifier
	 */
	public function getLayoutIdentifier($layoutName = 'Default') {
		$key = $layoutName . '_' . $this->getFormat();
		if (!array_key_exists($key, self::$resolvedIdentifiers['layouts'])) {
			$filePathAndFilename = $this->getLayoutPathAndFilename($layoutName);
			$prefix = 'layout_' . $layoutName;
			self::$resolvedIdentifiers['layouts'][$key] = $this->createIdentifierForFile($filePathAndFilename, $prefix);
		}
		return self::$resolvedIdentifiers['layouts'][$key];
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout file
	 * @throws InvalidTemplateResourceException
	 */
	public function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		return file_get_contents($layoutPathAndFilename);
	}

	/**
	 * Returns a unique identifier for the resolved template file
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $controller
	 * @param string $action Name of the action. If NULL, will be taken from request.
	 * @return string template identifier
	 */
	public function getTemplateIdentifier($controller = 'Default', $action = 'Default') {
		$format = $this->getFormat();
		if ($this->templateSource !== NULL) {
			return sha1($this->templateSource) . '_' . $controller . '_' . $action . '_' . $format;
		}
		$templatePathAndFilename = $this->resolveTemplateFileForControllerAndActionAndFormat($controller, $action, $format);
		$prefix = $controller . '_action_' . $action;
		return $this->createIdentifierForFile($templatePathAndFilename, $prefix);
	}

	/**
	 * @param mixed $source
	 */
	public function setTemplateSource($source) {
		$this->templateSource = $source;
	}

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $controller
	 * @param string $action Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws InvalidTemplateResourceException
	 */
	public function getTemplateSource($controller = 'Default', $action = 'Default') {
		if (is_string($this->templateSource)) {
			return $this->templateSource;
		} elseif (is_resource($this->templateSource)) {
			rewind($this->templateSource);
			return $this->templateSource = stream_get_contents($this->templateSource);
		}
		$format = $this->getFormat();
		$templateReference = $this->resolveTemplateFileForControllerAndActionAndFormat($controller, $action, $format);
		if (!file_exists($templateReference) && $templateReference !== 'php://stdin') {
			throw new InvalidTemplateResourceException(
				sprintf(
					'Tried resolving a template file for controller action "%s->%s" in format ".%s", but none of the paths ' .
					'contained the expected template file (%s). The following paths were checked: %s',
					$controller,
					$action,
					$format,
					$templateReference === NULL ? $controller . '/' . ucfirst($action) . '.' . $format : $templateReference,
					implode(',', $this->getTemplateRootPaths())
				),
				1257246929
			);
		}
		return file_get_contents($templateReference, FILE_TEXT);
	}

	/**
	 * Returns a unique identifier for the given file in the format
	 * <PackageKey>_<SubPackageKey>_<ControllerName>_<prefix>_<SHA1>
	 * The SH1 hash is a checksum that is based on the file path and last modification date
	 *
	 * @param string $pathAndFilename
	 * @param string $prefix
	 * @return string
	 */
	protected function createIdentifierForFile($pathAndFilename, $prefix) {
		$templateModifiedTimestamp = $pathAndFilename !== 'php://stdin' ? filemtime($pathAndFilename) : 0;
		return sprintf('%s_%s', $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->options['layoutPathAndFilename'] and $this->options['layoutPathAndFilenamePattern'].
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout files
	 * @throws Exception\InvalidTemplateResourceException
	 */
	public function getLayoutPathAndFilename($layoutName = 'Default') {
		$format = $this->getFormat();
		$layoutName = ucfirst($layoutName);
		$layoutKey = $layoutName . '.' . $format;
		if (!array_key_exists($layoutKey, self::$resolvedFiles['layouts'])) {
			$paths = $this->getLayoutRootPaths();
			self::$resolvedFiles['layouts'][$layoutKey] = $this->resolveFileInPaths($paths, $layoutName, $format);
		}
		return self::$resolvedFiles['layouts'][$layoutKey];
	}

	/**
	 * Returns a unique identifier for the resolved partial file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $partialName The name of the partial
	 * @return string partial identifier
	 */
	public function getPartialIdentifier($partialName) {
		$partialKey = $partialName . '.' . $this->getFormat();
		if (!array_key_exists($partialKey, self::$resolvedIdentifiers['partials'])) {
			$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
			$prefix = 'partial_' . $partialName;
			self::$resolvedIdentifiers['partials'][$partialKey] = $this->createIdentifierForFile($partialPathAndFilename, $prefix);
		}
		return self::$resolvedIdentifiers['partials'][$partialKey];
	}

	/**
	 * Figures out which partial to use.
	 *
	 * @param string $partialName The name of the partial
	 * @return string contents of the partial template
	 * @throws InvalidTemplateResourceException
	 */
	public function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		return file_get_contents($partialPathAndFilename, FILE_TEXT);
	}

	/**
	 * Resolve the partial path and filename based on $this->options['partialPathAndFilenamePattern'].
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws InvalidTemplateResourceException
	 */
	protected function getPartialPathAndFilename($partialName) {
		$format = $this->getFormat();
		$partialKey = $partialName . '.' . $format;
		if (!array_key_exists($partialKey, self::$resolvedFiles['partials'])) {
			$paths = $this->getPartialRootPaths();
			$partialName = ucfirst($partialName);
			self::$resolvedFiles['partials'][$partialKey] = $this->resolveFileInPaths($paths, $partialName, $format);
		}
		return self::$resolvedFiles['partials'][$partialKey];
	}

	/**
	 * @param array $paths
	 * @param string $relativePathAndFilename
	 * @return string
	 */
	protected function resolveFileInPaths(array $paths, $relativePathAndFilename, $format = self::DEFAULT_FORMAT) {
		$tried = array();
		foreach ($paths as $path) {
			$pathAndFilenameWithoutFormat = $path . $relativePathAndFilename;
			$pathAndFilename = $pathAndFilenameWithoutFormat . '.' . $format;
			if (is_file($pathAndFilename)) {
				return $pathAndFilename;
			}
			$tried[] = $pathAndFilename;
			if (is_file($pathAndFilenameWithoutFormat)) {
				return $pathAndFilenameWithoutFormat;
			}
			$tried[] = $pathAndFilenameWithoutFormat;
		}
		throw new InvalidTemplateResourceException(
			'The Fluid template files "' . implode('", "', $tried) . '" could not be loaded.',
			1225709595
		);
	}

	/**
	 * @return void
	 */
	protected function clearResolvedIdentifiersAndTemplates() {
		self::$resolvedIdentifiers = self::$resolvedFiles = array(
			'templates' => array(),
			'layouts' => array(),
			'partials' => array()
		);
	}

}
