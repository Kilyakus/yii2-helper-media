<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kilyakus\helper\media\extensions\Media;

interface MediaTypeInterface
{
    /**
     * Returns the available filters.
     */
    public function filters();

    /**
     * @return string
     */
    public function getPathfile();
}