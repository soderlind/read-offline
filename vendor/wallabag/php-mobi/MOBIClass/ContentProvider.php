<?php

abstract class ContentProvider
{
    /**
     * Get the text data to be integrated in the MOBI file.
     *
     * @return string
     */
    abstract public function getTextData();
    /**
     * Get the images (an array containing the jpeg data). Array entry 0 will
     * correspond to image record 0.
     *
     * @return array
     */
    abstract public function getImages();
    /**
     * Get the metadata in the form of a hashtable (for example, title or author).
     *
     * @return array
     */
    abstract public function getMetaData();
}
