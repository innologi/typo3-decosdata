<?php
namespace Innologi\Decosdata\Service;
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
/**
 * Download Service
 *
 * Facilitates downloading files if their direct path is not to be exposed
 * or if inaccessible for web access.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DownloadService implements SingletonInterface {

	/**
	 * @var boolean
	 */
	protected $validRequest = FALSE;

	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var integer
	 */
	protected $fileUid;

	/**
	 * @var string
	 */
	protected $salt = 'oiuJISF*(#J)#aF)J(Fg#JajO*!I#EW10GF$#*IJwFJLd635KIERGgfsjge43o8ui34wg4r';

	/**
	 * @var string
	 */
	protected $eID = 'tx_decosdata_download';

	/**
	 * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * Returns HashService
	 *
	 * @return \TYPO3\CMS\Extbase\Security\Cryptography\HashService
	 */
	protected function getHashService() {
		if ($this->hashService === NULL) {
			$this->hashService = GeneralUtility::makeInstance(
				\TYPO3\CMS\Extbase\Security\Cryptography\HashService::class
			);
		}
		return $this->hashService;
	}

	/**
	 * Returns UriBuilder
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected function getUriBuilder() {
		if ($this->uriBuilder === NULL) {
			$this->uriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(
				\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class
			);
		}
		return $this->uriBuilder;
	}

	/**
	 * Returns ResourceFactory
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected function getResourceFactory() {
		if ($this->resourceFactory === NULL) {
			$this->resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		}
		return $this->resourceFactory;
	}

	/**
	 * Returns the hash string used for input in generating and
	 * validating hashes
	 *
	 * @param mixed $params,...
	 * @return string
	 */
	protected function generateHashString(...$params) {
		return join('-', $params) . '|' . $this->salt;
	}

	/**
	 * Returns download URL
	 *
	 * @param integer $fileUid
	 * @param integer $blobUid
	 * @param integer $itemUid
	 * @return string
	 */
	public function getDownloadUrl($fileUid, $blobUid, $itemUid) {
		return $this->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->setUseCacheHash(FALSE)
			->setArguments([
				'eID' => $this->eID,
				'f' => $fileUid,
				'b' => $blobUid,
				'i' => $itemUid,
				'h' => $this->getHashService()->generateHmac(
					$this->generateHashString($fileUid, $blobUid, $itemUid)
				)
			])->buildFrontendUri();
	}

	/**
	 * Attempts to validate a request by checking parameters
	 *
	 * @throws \Exception
	 * @return $this
	 */
	public function validateRequest() {
		$this->fileUid = (int) GeneralUtility::_GP('f');
		$blobUid = (int) GeneralUtility::_GP('b');
		$itemUid = (int) GeneralUtility::_GP('i');
		$hash = (string) GeneralUtility::_GP('h');

		$this->validRequest = $this->getHashService()->validateHmac(
			$this->generateHashString($this->fileUid, $blobUid, $itemUid),
			$hash
		);

		if (!$this->validRequest) {
			throw new \Exception('Invalid request', 1515670080);
		}

		return $this;
	}

	/**
	 * Send file to user-agent and exit.
	 *
	 * @param boolean $noCache
	 * @return void
	 */
	public function sendFile($noCache = FALSE) {
		if (!$this->validRequest) {
			throw new \Exception('The request was not validated', 1515683722);
		}

		$file = $this->getResourceFactory()->getFileObject($this->fileUid);
		$filepath = PATH_site . $file->getPublicUrl();
		$filesize = filesize($filepath);

		// file transfer headers
		$headers = [
			// description of action
			'Content-Description' => 'File Transfer',
			// providing the right content type will tell the user-agent what to do
			'Content-Type' => $file->getMimeType() . '; charset=utf-8',
			// provide download dialog info, the quotes take care of spaces
			'Content-Disposition' => 'attachment; filename="' . $file->getName() . '"',
			// inform user-agent raw binary data is being transferred unencoded
			'Content-Transfer-Encoding' => 'binary',
			// allow bandwith optimization through byte-serving
			'Accept-Ranges' => 'bytes',
			// allows progress indication
			'Content-Length' => $filesize,
			// cookies provide unnecessary overhead at downloads
			'Set-Cookie' => NULL
		];

		if ($noCache) {
			// no caching headers (for frequent updates)
			// 'Expires: 0' does NOT always produce expected results
			$headers['Expires'] = 'Thu, 01 Jan 1970 00:00:00 GMT';
			$headers['Cache-Control'] = 'no-cache, must-revalidate';
			// losing these enforces use of Expires by user-agents that prefer
			// these or don't play nice with cache-control (e.g. HTTP/1.0)
			$headers['Last-Modified'] = NULL;
			$headers['ETag'] = NULL;
		}

		// in an extbase situation, we're deep in its outputbuffers, which could
		// (and often WILL) corrupt the download
		while (ob_end_clean()) {
			// keep going until we're out of them
		}
		// send headers after cleaning OB
		foreach ($headers as $header => $data) {
			header($header . ': ' . $data);
		}
		// before reading the file, we need to purge everything in our buffers
		// towards user-agent
		ob_flush();
		flush();

		// utilize a chunked-readfile due to possible configuration-specific
		// buffersize problems
		if (!$this->readfileByChunks($filepath)) {
			// .. unless it fails
			readfile($filepath);
		}

		// make sure the download is the ONLY thing done; end all
		// script-processing
		exit;
	}

	/**
	 * Variation of readfile(), to read by chunks.
	 * This variation
	 * is preferred over the original readfile(), due to buffer
	 * variations per server. Slightly altered version from the
	 * ones found in the comments in the PHP manual.
	 *
	 * @param string $file The file to read
	 * @param integer $chunksize The number of MB's per chunk
	 * @return boolean Answers whether reading the file was a success
	 * @see <http://www.php.net/manual/en/function.readfile.php>
	 */
	protected function readfileByChunks($file, $chunksize = 1) {
		$chunksize = $chunksize * (1024 * 1024);
		if (($fp = fopen($file, 'rb')) === FALSE) {
			return FALSE;
		}
		while (!feof($fp)) {
			$buffer = fread($fp, $chunksize);
			echo $buffer;
			ob_flush();
			flush();
		}
		return fclose($fp);
	}

}
