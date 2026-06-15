<?php

namespace App\Support;

/**
 * Builds the official loader snippet for a small allowlist of live-chat
 * providers. The page owner supplies only a provider key and their property ID;
 * we generate the canonical script ourselves so no arbitrary markup is injected.
 * The ID is expected to be pre-sanitized to [A-Za-z0-9_/.-] by the caller.
 */
class BioChat
{
    public const PROVIDERS = [
        'tawkto' => 'Tawk.to',
        'tidio' => 'Tidio',
        'intercom' => 'Intercom',
    ];

    public static function snippet(string $provider, string $id): string
    {
        $id = preg_replace('/[^A-Za-z0-9_\/.-]/', '', $id);
        if ($id === '' || ! array_key_exists($provider, self::PROVIDERS)) {
            return '';
        }

        return match ($provider) {
            'tawkto' => '<script>var Tawk_API=Tawk_API||{};(function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];s1.async=true;s1.src="https://embed.tawk.to/'.$id.'/default";s1.charset="UTF-8";s1.setAttribute("crossorigin","*");s0.parentNode.insertBefore(s1,s0);})();</script>',
            'tidio' => '<script src="//code.tidio.co/'.$id.'.js" async></script>',
            'intercom' => '<script>window.intercomSettings={app_id:"'.$id.'"};</script><script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic("reattach_activator");ic("update",w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement("script");s.type="text/javascript";s.async=true;s.src="https://widget.intercom.io/widget/'.$id.'";var x=d.getElementsByTagName("script")[0];x.parentNode.insertBefore(s,x);};if(document.readyState==="complete"){l();}else{w.addEventListener("load",l,false);}}})();</script>',
            default => '',
        };
    }
}
