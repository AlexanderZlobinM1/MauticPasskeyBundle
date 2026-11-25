<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LoginButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IntegrationHelper $integrationHelper,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', 0]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (method_exists($event, 'isMainRequest') && !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('login' !== (string) $request->attributes->get('_route') || $request->isXmlHttpRequest()) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject(MauticPasskeyIntegration::NAME);
        if (!$integration instanceof MauticPasskeyIntegration
            || !$integration->getIntegrationSettings()->isPublished()
        ) {
            return;
        }

        $response = $event->getResponse();
        if (200 !== $response->getStatusCode()) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ('' !== $contentType && false === stripos($contentType, 'html')) {
            return;
        }

        $content = (string) $response->getContent();
        if ('' === $content || false !== strpos($content, 'mautic-passkey-login-block')) {
            return;
        }

        try {
            $repository = $this->entityManager->getRepository(PasskeyCredential::class);
            $hasCredentials = $repository instanceof PasskeyCredentialRepository && $repository->countAll() > 0;
        } catch (\Throwable) {
            // Keep the native login usable while a just-installed plugin is
            // waiting for its migration. Registration remains unavailable
            // until Mautic creates the plugin-owned credential table.
            $hasCredentials = false;
        }
        $content = $this->removeStandardSsoLink($content);
        $block   = $this->renderButtonBlock($hasCredentials);
        $pos     = stripos($content, '</form>');
        $content = false === $pos
            ? str_ireplace('</body>', $block.'</body>', $content)
            : substr_replace($content, '</form>'.$block, $pos, 7);

        $response->setContent($content);
    }

    private function renderButtonBlock(bool $hasCredentials): string
    {
        $assertionUrl    = $this->router->generate('mautic_passkey_assertion_options');
        $registrationUrl = $this->router->generate('mautic_passkey_registration_options');
        $checkUrl        = $this->router->generate('mautic_sso_login_check', [
            'integration' => MauticPasskeyIntegration::NAME,
        ]);
        $primaryLabel = $this->translator->trans($hasCredentials
            ? 'mautic.passkey.login.button'
            : 'mautic.passkey.register.button');

        $strings = [
            'unsupported'          => $this->translator->trans('mautic.passkey.error.browser_unsupported'),
            'credentialsRequired'  => $this->translator->trans('mautic.passkey.error.credentials_required'),
            'authenticationFailed' => $this->translator->trans('mautic.passkey.error.authentication_failed'),
            'cancelled'            => $this->translator->trans('mautic.passkey.error.cancelled'),
            'registerFallback'     => $this->translator->trans('mautic.passkey.register.fallback'),
        ];

        $coordinator = file_get_contents(dirname(__DIR__).'/Assets/js/auth-alternative-coordinator.js');
        if (false === $coordinator) {
            throw new \RuntimeException('Passkey alternative-login coordinator is missing.');
        }

        $script = '<script>(function(){'.
            'var root=document.getElementById("mautic-passkey-login-block");if(!root){return;}'.
            'var primary=root.querySelector("[data-passkey-primary]");var fallback=root.querySelector("[data-passkey-register]");var error=root.querySelector("[data-passkey-error]");'.
            'var hasCredentials='.($hasCredentials ? 'true' : 'false').';'.
            'var assertionUrl='.$this->js($assertionUrl).';var registrationUrl='.$this->js($registrationUrl).';var strings='.$this->jsArray($strings).';'.
            'function field(id){var el=document.getElementById(id);return el?el.value:"";}'.
            'function csrf(){return field("_csrf_token")||((document.querySelector("input[name=_csrf_token]")||{}).value||"");}'.
            'function show(message){if(error){error.textContent=message||"";error.style.display=message?"block":"none";}}'.
            'function busy(value){primary.disabled=value;if(fallback){fallback.disabled=value;}}'.
            'function b64decode(value){value=value.replace(/-/g,"+").replace(/_/g,"/");value+="=".repeat((4-value.length%4)%4);var raw=atob(value),out=new Uint8Array(raw.length);for(var i=0;i<raw.length;i++){out[i]=raw.charCodeAt(i);}return out;}'.
            'function b64encode(value){var bytes=new Uint8Array(value),raw="";for(var i=0;i<bytes.length;i++){raw+=String.fromCharCode(bytes[i]);}return btoa(raw).replace(/\+/g,"-").replace(/\//g,"_").replace(/=+$/g,"");}'.
            'function createOptions(json){json.challenge=b64decode(json.challenge);json.user.id=b64decode(json.user.id);json.excludeCredentials=(json.excludeCredentials||[]).map(function(c){c.id=b64decode(c.id);return c;});return json;}'.
            'function requestOptions(json){json.challenge=b64decode(json.challenge);json.allowCredentials=(json.allowCredentials||[]).map(function(c){c.id=b64decode(c.id);return c;});return json;}'.
            'function credentialJson(credential){var r=credential.response;var response={clientDataJSON:b64encode(r.clientDataJSON)};if(r.attestationObject){response.attestationObject=b64encode(r.attestationObject);if(typeof r.getTransports==="function"){response.transports=r.getTransports();}}else{response.authenticatorData=b64encode(r.authenticatorData);response.signature=b64encode(r.signature);response.userHandle=r.userHandle?b64encode(r.userHandle):null;}return JSON.stringify({id:credential.id,rawId:b64encode(credential.rawId),type:credential.type,response:response,clientExtensionResults:credential.getClientExtensionResults?credential.getClientExtensionResults():{}});}'.
            'async function options(url,payload){var response=await fetch(url,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json","Accept":"application/json"},body:JSON.stringify(payload)});var data={};try{data=await response.json();}catch(e){}if(!response.ok){throw new Error(data.error||strings.authenticationFailed);}return data;}'.
            'function submit(kind,credential){var form=root.querySelector("form[data-passkey-submit]");form.querySelector("[name=_csrf_token]").value=csrf();form.querySelector("[name=passkey_ceremony]").value=kind;form.querySelector("[name=passkey_credential]").value=credentialJson(credential);form.submit();}'.
            'async function register(){var username=field("username"),password=field("password");if(!username||!password){show(strings.credentialsRequired);return;}busy(true);show("");try{var json=await options(registrationUrl,{username:username,password:password,_csrf_token:csrf()});var credential=await navigator.credentials.create({publicKey:createOptions(json)});if(!credential){throw new Error(strings.cancelled);}submit("registration",credential);}catch(e){show(e&&e.message?e.message:strings.authenticationFailed);busy(false);}}'.
            'async function authenticate(){busy(true);show("");if(fallback){fallback.style.display="none";}try{var json=await options(assertionUrl,{_csrf_token:csrf()});var credential=await navigator.credentials.get({publicKey:requestOptions(json)});if(!credential){throw new Error(strings.cancelled);}submit("assertion",credential);}catch(e){var canRegister=!!field("username")&&!!field("password");if(canRegister&&(e.name==="NotAllowedError"||e.name==="AbortError")){show(strings.registerFallback);if(fallback){fallback.style.display="block";}}else{show(e&&e.message?e.message:strings.authenticationFailed);}busy(false);}}'.
            'if(!window.PublicKeyCredential||!navigator.credentials){primary.disabled=true;show(strings.unsupported);return;}'.
            'primary.addEventListener("click",function(){hasCredentials?authenticate():register();});if(fallback){fallback.addEventListener("click",register);}'.
            '}());</script>';

        return '<style>'.
            '.mautic-auth-alternative-separator{display:flex;align-items:center;gap:10px;margin:14px 0 10px;color:#6f6f6f;font-size:13px;text-align:center}'.
            '.mautic-auth-alternative-separator:before,.mautic-auth-alternative-separator:after{content:"";height:1px;background:#d8dfe6;flex:1}'.
            '.mautic-passkey-login-block{clear:both;margin:10px 0 0}.mautic-passkey-button{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:46px}'.
            '.mautic-passkey-button .ri-fingerprint-line{font-size:21px}.mautic-passkey-error{display:none;margin-top:8px;color:#da1e28;font-size:13px}.mautic-passkey-register-fallback{display:none;margin-top:8px}'.
            '</style>'.
            '<div id="mautic-passkey-login-block" class="mautic-passkey-login-block" data-mautic-auth-alternative-block="1" data-mautic-auth-separator-label="'.$this->e($this->translator->trans('mautic.passkey.login.separator')).'">'.
            '<button type="button" class="btn btn-lg btn-block btn-secondary mautic-passkey-button mautic-auth-alternative-button" data-passkey-primary data-mautic-auth-alternative="1"><i class="ri-fingerprint-line" aria-hidden="true"></i><span>'.$this->e($primaryLabel).'</span></button>'.
            '<button type="button" class="btn btn-sm btn-ghost btn-block mautic-passkey-register-fallback" data-passkey-register>'.$this->e($this->translator->trans('mautic.passkey.register.fallback_button')).'</button>'.
            '<div class="mautic-passkey-error alert alert-danger" data-passkey-error role="alert" aria-live="polite"></div>'.
            '<form method="post" action="'.$this->e($checkUrl).'" data-passkey-submit style="display:none">'.
            '<input type="hidden" name="_csrf_token" value=""><input type="hidden" name="passkey_ceremony" value=""><input type="hidden" name="passkey_credential" value="">'.
            '</form></div><script>'.$coordinator.'</script>'.$script;
    }

    private function removeStandardSsoLink(string $content): string
    {
        $path = $this->router->generate('mautic_sso_login', ['integration' => MauticPasskeyIntegration::NAME]);
        $updated = preg_replace('#\s*<a\s+href="'.preg_quote($path, '#').'"[^>]*>.*?</a>#is', '', $content, 1);

        return is_string($updated) ? $updated : $content;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function js(string $value): string
    {
        return (string) json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    /** @param array<string, string> $values */
    private function jsArray(array $values): string
    {
        return (string) json_encode($values, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}

