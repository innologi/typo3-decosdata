<?php

namespace Innologi\Decosdata\Service\Option\Render;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\CommandRunService;
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\Decosdata\Service\Option\Exception\OptionException;
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;
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
class PdfSplit implements OptionInterface
{
    use Traits\FileHandler;
    use Traits\ExtensionConfiguration;

    /**
     * @var CommandRunService
     */
    protected $commandRunService;

    public function injectCommandRunService(CommandRunService $commandRunService): void
    {
        $this->commandRunService = $commandRunService;
    }

    /**
     * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
     */
    public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service)
    {
        if (($file = $this->getFileObject($service->getOriginalContent())) === null || !$this->isSupportedFile($file)) {
            return $tag;
        }

        if (!(isset($args['renderOptions']) && is_array($args['renderOptions']))) {
            throw new MissingArgument(1524141816, [self::class, 'renderOptions']);
        }

        $sitePath = $service->getSitePath();
        $outputPath = $sitePath . rtrim($this->getExtensionConfiguration('pdf_split_out'), '/') . '/' . $file->getSha1() . '/';
        $this->createDirectoryIfNotExists($outputPath);

        // first check if there are already splitted files at the original file's would-be output path
        $files = new \GlobIterator($outputPath . '*.pdf');
        if (!$files->valid()) {
            // if files do not exist, create them
            $files = $this->splitPdfFile($file, $sitePath, $outputPath);
        }
        $pdfPageCount = $files->count();

        // if pagination is active, apply \LimitIterator instead of going through $paginator->execute()
        $paginator = $service->getPaginator();
        if ($paginator !== null) {
            $files = new \LimitIterator(
                $files,
                $paginator->setTotal($pdfPageCount)->getOffset(),
                $paginator->getLimit(),
            );
        }

        $content = [];
        $service->setOptionVariables('PdfSplit', [
            'total' => $pdfPageCount,
        ]);
        /** @var \SplFileInfo $fileInfo */
        foreach ($files as $filePath => $fileInfo) {
            $content[] = $service->processOptions(
                $args['renderOptions'],
                // @FIX consider that if options fail, this may result in outputted serverpaths. in our use-case, this must never be the case
                'mockfile:' . $filePath,
                $service->getIndex() . $fileInfo->getBasename('.pdf'),
                $service->getItem(),
            );
        }
        $service->unsetOptionVariables('PdfSplit');

        // if pagination is active, apply XHR elements and next link
        $separator = $args['separator'] ?? '';
        $content = $paginator !== null && $paginator->isXhrEnabled() ? $paginator->xhrWrapping($content, $separator) : \join($separator, $content);

        if ($tag instanceof TagContent) {
            return $tag->reset()->setContent($content);
        }

        return $service->getTagFactory()->createTagContent($content);
    }

    public function paginateIterate(array $args, RenderOptionService $service): void
    {
        // do nothing; pagination is based on \LimitIterator in $this->alterContentValue()
    }

    /**
     * Return whether we support this file, i.e. an existing PDF
     *
     * @return boolean
     */
    protected function isSupportedFile(AbstractFile $file)
    {
        return $file->exists() && (str_contains($file->getMimeType(), '/pdf'));
    }

    /**
     * Splits PDF files by configured command
     *
     * Supports the following variables in the command:
     * - OUTPUTDIR
     * - INPUTFILE
     *
     * @param string $sitePath
     * @param string $outputPath
     * @throws OptionException
     * @return \GlobIterator
     */
    protected function splitPdfFile(AbstractFile $inputFile, $sitePath, $outputPath)
    {
        $cmdOutput = $this->commandRunService
            ->reset()
            ->setAllowBinaries([
                'pdftk',
                'pdf*',
            ])->runCommand(
                $this->getExtensionConfiguration('pdf_split_cmd'),
                [
                    'OUTPUTDIR' => $outputPath,
                    'INPUTFILE' => $sitePath . $inputFile->getPublicUrl(),
                ],
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
