<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Shlinkio\Shlink\Rest\Service\RestTokenService;
use Shlinkio\Shlink\Rest\Service\RestTokenServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class AuthenticateMiddleware extends AbstractRestMiddleware
{
    /**
     * @var RestTokenServiceInterface
     */
    private $restTokenService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AuthenticateMiddleware constructor.
     * @param RestTokenServiceInterface|RestTokenService $restTokenService
     * @param TranslatorInterface $translator
     *
     * @Inject({RestTokenService::class, "translator"})
     */
    public function __construct(RestTokenServiceInterface $restTokenService, TranslatorInterface $translator)
    {
        $this->restTokenService = $restTokenService;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        $authData = $request->getParsedBody();
        if (! isset($authData['username'], $authData['password'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $this->translator->translate('You have to provide both "username" and "password"'),
            ], 400);
        }

        try {
            $token = $this->restTokenService->createToken($authData['username'], $authData['password']);
            return new JsonResponse(['token' => $token->getToken()]);
        } catch (AuthenticationException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => $this->translator->translate('Invalid username and/or password'),
            ], 401);
        }
    }
}
