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
	 * @var \Innologi\Decosdata\Service\CommandRunService
	 * @inject
	 */
	protected $commandRunService;

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( ($file = $this->getFileObject($service->getOriginalContent())) === NULL || !$this->isSupportedFile($file) ) {
			return $tag;
		}

		if (! (isset($args['renderOptions']) && is_array($args['renderOptions'])) ) {
			throw new MissingArgument(1524141816, [self::class, 'renderOptions']);
		}

		$outputPath = PATH_site . rtrim($this->getExtensionConfiguration('pdf_split_out'), '/') . '/' . $file->getSha1() . '/';
		$this->createDirectoryIfNotExists($outputPath);

		// first check if there are already splitted files at the original file's would-be output path
		$files = new \GlobIterator($outputPath . '*.pdf');
		if (!$files->valid()) {
			// if files do not exist, create them
			$files = $this->splitPdfFile($file, $outputPath);
		}

		// if pagination is active, apply \LimitIterator instead of going through $paginator->execute()
		$paginator = $service->getPaginator();
		if ($paginator !== NULL) {
			$files = new \LimitIterator(
				$files,
				$paginator->setTotal($files->count())->getOffset(),
				$paginator->getLimit()
			);
		}

		$separator = $args['separator'] ?? '';
		$content = [];
		/** @var \SplFileInfo $fileInfo */
		foreach ($files as $filePath => $fileInfo) {
			$content[] = $service->processOptions(
				$args['renderOptions'],
				// @FIX consider that if options fail, this may result in outputted serverpaths. in our use-case, this must never be the case
				'mockfile:' . $filePath,
				$service->getIndex() . $fileInfo->getBasename('.pdf'),
				$service->getItem()
			);
		}

		// if pagination is active, apply XHR elements and next link
		$content = $paginator !== NULL && $paginator->isXhrEnabled() ? $paginator->xhrWrapping($content, $separator) : \join($separator, $content);

		if ($tag instanceof TagContent) {
			return $tag->reset()->setContent($content);
		}

		return $service->getTagFactory()->createTagContent($content);
	}

	public function paginateIterate(array $args, RenderOptionService $service) {
		// do nothing; pagination is based on \LimitIterator in $this->alterContentValue()
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
	 * - OUTPUTDIR
	 * - INPUTFILE
	 *
	 * @param AbstractFile $inputFile
	 * @param string $outputPath
	 * @throws OptionException
	 * @return \GlobIterator
	 */
	protected function splitPdfFile(AbstractFile $inputFile, $outputPath) {
		$cmdOutput = $this->commandRunService
			->reset()
			->setAllowBinaries([
				'pdftk',
				'pdf*'
			])->runCommand(
				$this->getExtensionConfiguration('pdf_split_cmd'),
				[
					'OUTPUTDIR' => $outputPath,
					'INPUTFILE' => PATH_site . $inputFile->getPublicUrl()
				]
			);

		// get an iterator containing \SplFileInfo instances
		$files = new \GlobIterator($outputPath . '*.pdf');
		if (!$files->valid()) {
			throw new OptionException(1524141951, ['Failed to retrieve PdfSplit output files.']);
			// @LOW log lastRunCommand + $cmdOutput
		}
		return $files;
	}

}
