<?php
namespace Innologi\Decosdata\Service\Option\Render;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\TagBuilder\TagInterface;
use Innologi\TagBuilder\TagContent;
use TYPO3\CMS\Core\Resource\AbstractFile;
/**
 * PDF Foreach Page option
 *
 * Counts the page number and is able to run through given renderOptions
 * for each of these pages. Injects the page index into {PdfForeachPage:index}
 * var in the given renderOptions.
 *
 * Expects extension configuration to contain:
 * - pdf_info_cmd
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PdfForeachPage implements OptionInterface {
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
			throw new MissingArgument(1524141815, [self::class, 'renderOptions']);
		}

		$separator = $args['separator'] ?? '';
		$content = [];
		$pdfPageCount = $this->getPdfPageCount($file);
		$paginator = $service->getPaginator();
		if ($paginator !== NULL) {
			// returns a complete and paginated result
			$content = $paginator->setTotal($pdfPageCount)->execute($separator);
		} else {
			// unpaginated result
			for ($i = 0; $i < $pdfPageCount; $i++) {
				$content[] = $this->createContent($i, $pdfPageCount, $args['renderOptions'], $service);
			}
			$content = join($separator, $content);
		}

		if ($tag instanceof TagContent) {
			return $tag->reset()->setContent($content);
		}

		return $service->getTagFactory()->createTagContent($content);
	}

	public function paginateIterate(array $args, RenderOptionService $service) {
		return $this->createContent(
			$service->getPaginator()->getIterationIndex(),
			$service->getPaginator()->getTotal(),
			$args['renderOptions'],
			$service
		);
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
	 * Returns the PDF page count
	 *
	 * Supports the following variables in the command:
	 * - INPUTFILE
	 *
	 * @param AbstractFile $inputFile
	 * @return integer
	 */
	protected function getPdfPageCount(AbstractFile $inputFile) {
		$cmdOutput = $this->commandRunService
			->reset()
			->setCommandLimit(3)
			->setCommandSubstitutes([
				'GREP:$1:escapeshellarg' => 'grep $1',
				'AWKPRINT:$1:intval' => 'awk \'{print $$1}\''
			])->setAllowBinaries([
				'pdftk',
				'pdfinfo',
				'pdf*'
			])->runCommand(
				$this->getExtensionConfiguration('pdf_info_cmd'),
				[
					'INPUTFILE' => PATH_site . $inputFile->getPublicUrl()
				]
			);

		if (! (isset($cmdOutput[0]) && \is_numeric($cmdOutput[0])) ) {
			// @TODO throw exception
		}

		return (int) $cmdOutput[0];
	}

	/**
	 * Create content through sub-renderOptions
	 *
	 * @param integer $pageIndex
	 * @param integer $total
	 * @param array $renderOptions
	 * @param RenderOptionService $service
	 * @return \Innologi\TagBuilder\TagInterface
	 */
	protected function createContent($pageIndex, $total, array $renderOptions, RenderOptionService $service) {
		$service->setOptionVariables('PdfForeachPage', [
			'index' => $pageIndex,
			'total' => $total
		]);
		$content = $service->processOptions(
			$renderOptions,
			$service->getOriginalContent(),
			$service->getIndex() . 'p' . $pageIndex,
			$service->getItem()
		);
		$service->unsetOptionVariables('PdfForeachPage');
		return $content;
	}
}
