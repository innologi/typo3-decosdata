<?php
namespace Innologi\Decosdata\Service\Option\Render;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\Decosdata\Service\Option\Exception\OptionException;
use Innologi\TagBuilder\TagInterface;
use Innologi\TagBuilder\TagContent;
use TYPO3\CMS\Core\Resource\AbstractFile;
/**
 * PDF Split option
 *
 * Splits a multi-page PDF into multiple single page PDF files.
 *
 * Expects extension configuration to contain:
 * - pdf_split_out
 * - pdf_split_cmd
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PdfSplit implements OptionInterface {
	use Traits\FileHandler;
	use Traits\ExtensionConfiguration;

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( ($file = $this->getFileObject($service->getOriginalContent())) === NULL || !$this->isSupportedFile($file) ) {
			return $tag;
		}

		if (! (isset($args['renderOptions']) && is_array($args['renderOptions'])) ) {
			throw new MissingArgument(1524141815, [self::class, 'renderOptions']);
		}

		$outputPath = PATH_site . rtrim($this->getExtensionConfiguration('pdf_split_out'), '/') . '/' . $file->getSha1() . '/';
		$this->createDirectoryIfNotExists($outputPath);

		// first check if there are already splitted files at the original file's would-be output path
		$files = new \GlobIterator($outputPath . '*.pdf');
		if (!$files->valid()) {
			// if files do not exist, create them
			$files = $this->splitPdfFile($file, $outputPath);
		}

		$content = [];
		$i = 0;
		/** @var \SplFileInfo $fileInfo */
		foreach ($files as $filePath => $fileInfo) {
			$pageIndex = $service->getIndex() . $fileInfo->getBasename('.pdf');
			$content[] = $service->processOptions(
				$args['renderOptions'],
				// @FIX consider that if options fail, this may result in outputted serverpaths. in our use-case, this must never be the case
				'mockfile:' . $filePath,
				$pageIndex,
				$service->getItem()
			);
		}
		$content = join($args['separator'] ?? '', $content);

		if ($tag instanceof TagContent) {
			return $tag->reset()->setContent($content);
		}

		return $service->getTagFactory()->createTagContent($content);
	}

	/**
	 * Return whether we support this file, i.e. an existing PDF
	 *
	 * @param AbstractFile $file
	 * @return boolean
	 */
	protected function isSupportedFile(AbstractFile $file) {
		return $file->exists() && (strpos($file->getMimeType(), '/pdf') !== FALSE);
	}

	/**
	 * Splits PDF files by configured command
	 *
	 * Supports the following variables in the command:
	 * - $OUTPUTDIR
	 * - $INPUTFILE
	 *
	 * @param AbstractFile $inputFile
	 * @param string $outputPath
	 * @throws OptionException
	 * @return \GlobIterator
	 */
	protected function splitPdfFile(AbstractFile $inputFile, $outputPath) {
		$inputPath = PATH_site . $inputFile->getPublicUrl();

		$cmd = $this->getExtensionConfiguration('pdf_split_cmd');
		$cmd = str_replace('$OUTPUTDIR', '\'' . $outputPath . '\'', $cmd);
		$cmd = str_replace('$INPUTFILE', '\'' . $inputPath . '\'', $cmd);
		$cmdOutput = NULL;
		$cmdStatus = NULL;
		exec(escapeshellcmd($cmd), $cmdOutput, $cmdStatus);

		if ($cmdStatus !== 0) {
			// anything else is an error exit code
			throw new OptionException(1524141893, ['Failed to run PdfSplit command.']);
			// @LOW log $cmd + $cmdOutput + $cmdStatus
		}

		// get an iterator containing \SplFileInfo instances
		$files = new \GlobIterator($outputPath . '*.pdf');
		if (!$files->valid()) {
			throw new OptionException(1524141951, ['Failed to retrieve PdfSplit output files.']);
			// @LOW log $cmd + $cmdOutput
		}

		return $files;
	}

}
