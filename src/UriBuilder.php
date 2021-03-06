<?php

namespace Fazed\UriBuilder;

class UriBuilder
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $sections = [];

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $fileExtension = null;

    /**
     * @var bool
     */
    private $secured = false;

    /**
     * Create new UriBuilder instance.
     *
     * @param  string  $base
     * @param  boolean $secured
     */
    public function __construct($base, $secured = false)
    {
        $this->setBaseUrl($base);
        $this->setSecured($secured);
    }

    /**
     * Staticly create a new UriBuilder instance.
     *
     * @param  string  $base
     * @param  boolean $secured
     * @return $this
     */
    public static function make(string $base, bool $secured = false): UriBuilder
    {
        return new self($base, $secured);
    }

    /**
     * Break down the given base into an url, sections & params.
     *
     * @param  string $base
     * @return $this
     * @throws \Exception
     */
    public function setBaseUrl(string $base): UriBuilder
    {
        if (! static::validateUrl($base)) {
            throw new \Exception('Passed string not a valid URL');
        }

        $url = rtrim(preg_replace('/^(http|https):\/\//', '', $base));

        if (strpos($url, '?') !== false) {
            $this->setParameters($this->parseParameters($url));
            $url = rtrim(substr($url, 0, strpos($url, '?')), '/');
        }

        if (strpos($url, '/') !== false) {
            $this->setSections($this->parseSections($url));
            $url = rtrim(substr($url, 0, strpos($url, '/')), '/');
        }

        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    /**
     * Get the base url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the build url.
     *
     * @param  bool $trailingSlash
     * @return string
     */
    public function getBuildUrl(bool $trailingSlash = false): string
    {
        return $this->buildUrl($trailingSlash);
    }

    /**
     * Set the url to be (un)secured (http/https).
     *
     * @param  bool $secured
     * @return $this
     */
    public function setSecured($secured): UriBuilder
    {
        $this->secured = $secured;

        return $this;
    }

    /**
     * Get the url file extension.
     *
     * @return mixed
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * Set the url file extension.
     *
     * @param string $extension
     */
    public function setFileExtension($extension): UriBuilder
    {
        $this->fileExtension = $extension;

        return $this;
    }

    /**
     * Get the urls sections.
     *
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Set the sections for the url.
     *
     * @param  array $sections
     * @return $this
     */
    public function setSections(array $sections): UriBuilder
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Append sections to the url.
     *
     * @param  array $sections
     * @return $this
     */
    public function appendSections(...$sections): UriBuilder
    {
        $this->sections = array_merge($this->sections, $sections);

        return $this;
    }

    /**
     * Prepend new sections to the url.
     *
     * @param  array $sections
     * @return $this
     */
    public function prependSections(...$sections): UriBuilder
    {
        $this->sections = array_merge($sections, $this->sections);

        return $this;
    }

    /**
     * Remove the first section from the url.
     *
     * @return $this
     */
    public function shiftSection(): UriBuilder
    {
        if (sizeof($this->parameters) > 0) {
            $this->sections = array_shift($this->sections);
        }

        return $this;
    }

    /**
     * Remove the last section from the url.
     *
     * @return $this
     */
    public function popSection(): UriBuilder
    {
        if (sizeof($this->parameters) > 0) {
            $this->sections = array_pop($this->sections);
        }

        return $this;
    }

    /**
     * Remove a section from the url.
     *
     * @param  string $section
     * @param  int    $limit
     * @return $this
     */
    public function removeSection(string $section, int $limit = 0): UriBuilder
    {
        $instances = array_keys($this->sections, $section);

        if (sizeof($instances) > 0) {
            if ($limit < 0) {
                $limit = sizeof($instances);
            }

            $index = 0;

            while ($index < $limit || $index < sizeof($instances)) {
                unset($this->sections[$instances[$index]]);
            }

            $this->sections = array_values($this->sections);
        }

        return $this;
    }

    /**
     * Get the urls parameters.
     *
     * @param  bool $stringfy
     * @return mixed
     */
    public function getParameters(bool $stringfy = false)
    {
        return $stringfy ? $this->buildParamString() : $this->parameters;
    }

    /**
     * Set the parameters for the url.
     *
     * @param  array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): UriBuilder
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     *	Append new parameters to the url.
     *
     * @param  array $parameters
     * @return $this
     */
    public function appendParameters(...$parameters): UriBuilder
    {
        foreach ($parameters as $params) {
            $this->parameters = array_merge($this->parameters, $params);
        }

        return $this;
    }

    /**
     * Remove the first parameter from parameters.
     *
     * @return $this
     */
    public function shiftParameter(): UriBuilder
    {
        if (sizeof($this->parameters) > 0) {
            $this->parameters = array_shift($this->parameters);
        }

        return $this;
    }

    /**
     * Remove the last parameters from parameters.
     *
     * @return $this
     */
    public function popParameter(): UriBuilder
    {
        if (sizeof($this->parameters) > 0) {
            $this->parameters = array_pop($this->parameters);
        }

        return $this;
    }

    /**
     * Build the url.
     *
     * @param  bool $trailingSlash
     * @return string
     */
    private function buildUrl(bool $trailingSlash): string
    {
        $url = sprintf('http%s://%s/', $this->secured ? 's' : '', $this->baseUrl);
        $url .= ltrim($this->buildSectionString($trailingSlash), '/');
        $url .= $this->fileExtension ? ".{$this->fileExtension}" : '';
        $url .= $this->buildParamString();

        return $url;
    }

    /**
     * Build a section string from sections;
     *
     * @param  bool $trailing
     * @return string
     */
    private function buildSectionString(bool $trailing): string
    {
        if (sizeof($this->sections)) {
            return implode('/', $this->sections) . ($trailing ? '/' : '');
        }

        return '';
    }

    /**
     * Build a parameter string from parameters.
     *
     * @return string
     */
    private function buildParamString(): string
    {
        $paramString = sizeof($this->parameters) ? '?' : '';

        foreach ($this->parameters as $key=>$value) {
            $paramString .= sprintf('%s=%s&', $key, urlencode($value));
        }

        return rtrim($paramString, '&');
    }

    /**
     * Parse sections from given url.
     *
     * @param  string $url
     * @return array
     */
    private function parseSections(string $url): array
    {
        $sections = explode('/', $url);

        return sizeof($sections) > 1 ? array_splice($sections, 1) : [];
    }

    /**
     * Parse parameters from given url.
     *
     * @param  string $url
     * @return array
     * @throws \Exception
     */
    private function parseParameters(string $url): array
    {
        if (preg_match_all('/(?<=\?|\&)([a-zA-z]+)\=([a-zA-z0-9]+)?/', $url, $matches, PREG_SET_ORDER) === false) {
            throw new \Exception('An error occured while parsing url parameters.');
        }

        $parameters = [];

        foreach ($matches as $match) {
            $parameters[$match[1]] = $match[2];
        }

        return $parameters;
    }

    /**
     * Validate the given url.
     *
     * @param  string $url
     * @return bool
     */
    public static function validateUrl(string $url): bool
    {
        $regex = '/(?i)\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?Ã‚Â«Ã‚Â»Ã¢â‚¬Å“Ã¢â‚¬ÂÃ¢â‚¬ËœÃ¢â‚¬â„¢]))/';

        return preg_match($regex, $url) === 1;
    }

    /**
     * Return build url without trailing slash.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getBuildUrl();
    }
}
