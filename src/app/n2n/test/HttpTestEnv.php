<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\test;

use n2n\web\http\HttpContext;
use n2n\web\http\SimpleRequest;
use n2n\core\N2N;
use n2n\util\uri\Query;
use n2n\web\http\Method;
use n2n\util\uri\Url;
use n2n\web\http\controller\ControllerRegistry;
use n2n\core\container\impl\AppN2nContext;
use n2n\web\http\Response;
use n2n\util\StringUtils;
use n2n\core\container\N2nContext;
use n2n\web\http\SimpleSession;
use n2n\web\http\UploadDefinition;
use n2n\util\io\fs\FsPath;
use n2n\util\io\IoUtils;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\ex\IllegalStateException;
use n2n\web\ext\HttpContextFactory;
use n2n\web\ext\HttpAddonContext;
use n2n\web\http\ResponseCacheStore;
use n2n\web\http\payload\Payload;
use n2n\web\http\cache\PayloadCacheStore;
use n2n\web\http\FlushMode;
use n2n\util\ex\UnsupportedOperationException;

class HttpTestEnv {

	/**
	 * @var N2nContext
	 */
	private $n2nContext;

	/**
	 * @param N2nContext $n2nContext
	 */
	function __construct(AppN2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
	}

	/**
	 * @param string $subsystem
	 * @param Url $contextUrl
	 * @return TestRequest
	 */
	function newRequest($subsystemName = null, Url $contextUrl = null) {
		if ($contextUrl === null) {
			$contextUrl = Url::create('https://www.test-url.ch/');
		}

		$request = new SimpleRequest($contextUrl);
		$request->setN2nLocale($this->n2nContext->getN2nLocale());

		$appN2nContext = TestEnv::forkN2nContext();
		$appConfig = N2N::getAppConfig();

		$responseCacheStore = new ResponseCacheStore($appN2nContext->getAppCache(), $appN2nContext->getTransactionManager());
		$payloadCacheStore = new PayloadCacheStore($appN2nContext->getAppCache(), $appN2nContext->getTransactionManager());
		$httpContext = HttpContextFactory::createFromAppConfig($appConfig, $request, new SimpleSession(),
				$appN2nContext, $responseCacheStore, null);

		$controllerRegistry = new ControllerRegistry($appConfig->web(), $appConfig->routing());
		$controllerInvoker = new HttpAddonContext($httpContext, $controllerRegistry, $responseCacheStore, $payloadCacheStore);

		// TODO: think of some better way.
		foreach ($appN2nContext->getAddonContexts() as $addonContext) {
			if (!($addonContext instanceof HttpAddonContext)) {
				continue;
			}

			$addonContext->finalize();
			$appN2nContext->removeAddonContext($addonContext);
		}

		$appN2nContext->removeAddonContextByType(HttpAddonContext::class);
		$appN2nContext->setHttp($controllerInvoker);
		$appN2nContext->addAddonContext($controllerInvoker);
		// END TODO: think of some better way.

		if ($subsystemName !== null) {
			$httpContext->setActiveSubsystemRule($httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale($subsystemName));
		}

//		$pdoPool = $appN2nContext->lookup(PdoPool::class);
//		foreach ($this->n2nContext->lookup(PdoPool::class)->getInitializedPdos() as $puName => $pdo) {
//			$pdoPool->setPdo($puName, $pdo);
//		}

		return new TestRequest($httpContext, $request);
	}

	/**
	 * @param FsPath $fsPath
	 * @return UploadDefinition
	 */
	function newUploadDefinitionFromFsPath(FsPath $fsPath) {
		$tmpFile = tempnam(sys_get_temp_dir(), 'n2n-test');
		IoUtils::putContents($tmpFile, IoUtils::getContents((string) $fsPath));

		return new UploadDefinition(UPLOAD_ERR_OK, $fsPath->getName(), $tmpFile,
				mime_content_type((string) $fsPath), $fsPath->getSize());
	}
}

class TestRequest {

	/**
	 * @param HttpContext $httpContext
	 * @param SimpleRequest $simpleRequest
	 */
	function __construct(private HttpContext $httpContext, private SimpleRequest $simpleRequest) {
	}

	/**
	 * @param \Closure $closure
	 * @return $this
	 * @throws \ReflectionException
	 */
	function inject(\Closure $closure): static {
		$invoker = new MagicMethodInvoker($this->httpContext->getN2nContext());
		$invoker->invoke(null, new \ReflectionFunction($closure));
		return $this;
	}

	function putLookupInjection(string $className, object $obj): static {
		$this->httpContext->getN2nContext()->putLookupInjection($className, $obj);
		return $this;
	}

	function removeLookupInjection(string $className) {
		$this->httpContext->getN2nContext()->removeLookupInjection($className);
		return $this;
	}

	/**
	 * @param string $name
	 * @return \n2n\test\TestRequest
	 */
	function subsystem(?string $name): static {
		if ($name === null) {
			$this->httpContext->setActiveSubsystemRule(null);
			return $this;
		}

		$this->httpContext->setActiveSubsystemRule($this->httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale($name));
		return $this;
	}

	/**
	 * @return \n2n\test\TestRequest
	 */
	function get($cmdUrl): static {
		$this->simpleRequest->setMethod(Method::GET);
		$this->simpleRequest->setCmdUrl(Url::create($cmdUrl));
		return $this;
	}

	/**
	 * @return \n2n\test\TestRequest
	 */
	function put($cmdUrl): static {
		$this->simpleRequest->setMethod(Method::PUT);
		$this->simpleRequest->setCmdUrl(Url::create($cmdUrl));
		return $this;
	}

	/**
	 * @return \n2n\test\TestRequest
	 */
	function delete($cmdUrl): static {
		$this->simpleRequest->setMethod(Method::DELETE);
		$this->simpleRequest->setCmdUrl(Url::create($cmdUrl));
		return $this;
	}

	/**
	 * @param mixed $cmdUrl will be passed to {@see Url::create()} for creation
	 * @param mixed $postQuery will be passed to {@see Query::create()} for creation
	 * @return \n2n\test\TestRequest
	 */
	function post($cmdUrl, $postQuery = null): static {
		$this->simpleRequest->setMethod(Method::POST);
		$this->simpleRequest->setCmdUrl(Url::create($cmdUrl));

		if ($postQuery !== null) {
			$this->simpleRequest->setPostQuery(Query::create($postQuery));
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return \n2n\test\TestRequest
	 */
	function header(string $name, string $value): static {
		$this->simpleRequest->setHeader($name, $value);
		return $this;
	}

	/**
	 * @param string|null $body
	 * @return \n2n\test\TestRequest
	 */
	function body(?string $body): static {
		$this->simpleRequest->setBody($body);
		return $this;
	}

	/**
	 * @param array $data
	 * @return \n2n\test\TestRequest
	 */
	function bodyJson(array $data) {
		return $this->body(StringUtils::jsonEncode($data));
	}

	/**
	 * @param UploadDefinition[] $uploadDefinitions
	 * @return \n2n\test\TestRequest
	 */
	function uploadDefinitions(array $uploadDefinitions) {
		$this->simpleRequest->setUploadDefinitions($uploadDefinitions);
		return $this;
	}

	/**
	 * @return TestResponse
	 */
	function exec(bool $sendStatusView = false) {
		if ($this->httpContext->getN2nContext()->getTransactionManager()->hasOpenTransaction()) {
			throw new IllegalStateException('Can not execute TestRequest inside a N2nContext with an active transaction.');
		}

		/**
		 * @var ControllerRegistry $controllerRegistry
		 */
		$controllerRegistry = $this->httpContext->getN2nContext()->lookup(ControllerRegistry::class);

		$controllingPlan = $controllerRegistry
				->createControllingPlan($this->httpContext, $this->simpleRequest->getCmdPath(),
						$this->httpContext->getActiveSubsystemRule());
		$result = $controllingPlan->execute();

		if (!$result->isSuccessful()) {
			if (!$sendStatusView) {
				$this->httpContext->getN2nContext()->finalize();
				throw $result->getStatusException();
			}

			$controllingPlan->sendStatusView($result->getStatusException());
		}

		$response = $this->httpContext->getResponse();
		$response->flush(FlushMode::SILENT);
		$response->closeBuffer();

		$this->httpContext->getN2nContext()->finalize();

		return new TestResponse($response);
	}

	function __destruct() {
		$n2nContext = $this->httpContext->getN2nContext();
		if (!$n2nContext->isFinalized()) {
			$n2nContext->finalize();
		}
	}
}

class TestResponse {
	private $response;

	function __construct(Response $response) {
		$this->response = $response;
	}

	function parseJson(): ?array {
		if (null !== ($contents = $this->getContents())) {
			return StringUtils::jsonDecode($contents, true);
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	function getContents(): ?string {
		if ($this->isBufferable()) {
			return $this->response->getBufferableOutput();
		}

		throw new UnsupportedOperationException('Sent Payload is not bufferable.');
	}

	/**
	 * @return bool
	 */
	function isBufferable(): bool {
		return $this->response->getSentPayload()->isBufferable();
	}

	/**
	 * @return Payload
	 */
	function getSentPayload(): Payload {
		return $this->response->getSentPayload();
	}

	/**
	 * @return int
	 */
	function getStatus(): int {
		return $this->response->getStatus();
	}
}
