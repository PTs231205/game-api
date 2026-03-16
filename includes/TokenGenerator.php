<?php

final class TokenGenerator
{
    /**
     * 256-word list (256^6 ~= 2^48 combinations).
     * Keep words lowercase + url-safe.
     */
    private const WORDS = [
        'able','acid','aged','also','amid','angel','apple','april','arena','argue','arrow','atlas','audio','aunt','awake','axis',
        'baby','bacon','badge','baker','basic','beach','beacon','beard','beast','bench','berry','bingo','birth','black','blade','bless',
        'blue','bonus','boost','brain','brave','bread','brick','brief','bring','broad','buddy','build','cable','camel','candy','cargo',
        'carry','catch','cause','chain','chair','chalk','charm','chase','cheap','check','cheer','chess','chief','child','civic','claim',
        'class','clean','clear','clerk','click','clock','close','coach','coast','color','comet','copper','coral','count','craft','crane',
        'crisp','cross','crown','curve','cycle','daily','dance','daring','dawn','debug','delay','delta','demon','dense','desk','digit',
        'dizzy','dodge','donor','draft','drama','dream','dress','drift','drink','drive','dune','eager','early','earth','easel','echo',
        'elite','ember','empty','enjoy','entry','equal','error','ethic','event','exact','extra','faith','false','fancy','feast','fiber',
        'field','final','flame','flash','fleet','floor','focus','force','fresh','frost','fruit','giant','given','glass','glide','glory',
        'gold','grace','grain','grant','graph','green','grip','group','guard','guess','habit','happy','harbor','harden','hatch','heart',
        'heavy','honey','honor','horse','hotel','human','humble','ideal','image','index','inner','input','iron','ivory','jelly','jewel',
        'joint','judge','juice','kitty','knife','knock','label','laser','later','laugh','layer','lemon','level','light','limit','linen',
        'logic','lucky','lunar','magic','major','maple','march','match','maybe','medal','metal','midst','minor','model','money','month',
        'moral','motor','mouse','movie','music','naive','narrow','navy','neat','never','noble','noise','north','novel','nurse','oasis',
        'ocean','offer','olive','onion','opera','orbit','order','other','owner','panel','paper','party','patch','peace','pearl','pencil',
        'phase','phone','piano','pilot','pinky','pizza','place','plain','plant','plate','point','power','pride','prime','print','prize',
        'proof','proud','pulse','punch','queen','quick','quiet','radar','rain','raise','range','rapid','ratio','ready','relay','reset',
        'rider','right','risky','river','robot','rough','round','royal','ruler','salad','scale','scene','scope','score','screw','seed',
        'sense','serve','shade','shake','share','sharp','sheet','shift','shine','ship','shock','short','sight','since','skill','sleep',
        'smart','smile','solid','sound','south','space','spark','speed','spice','spike','spirit','split','spoon','sport','stack','stage',
        'stand','start','steam','steel','stone','store','storm','story','strip','style','sugar','sunny','super','swift','table','tango',
        'target','teal','tempo','thank','thick','thing','tiger','title','toast','today','token','topic','torch','total','tower','trace',
        'trade','train','treat','trend','trial','trust','truth','tulip','tune','tutor','twist','union','unit','upper','urban','usage',
        'valid','value','vapor','vivid','voice','watch','water','weary','whale','wheat','white','whole','wider','winner','winter','wired',
        'woman','world','worry','zebra','zesty','zonal',
    ];

    public static function words(int $count = 6, string $sep = '-'): string
    {
        $count = max(1, $count);
        $max = count(self::WORDS) - 1;
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = self::WORDS[random_int(0, $max)];
        }
        return implode($sep, $out);
    }

    public static function digits(int $length = 6): string
    {
        $length = max(1, $length);
        $max = (10 ** $length) - 1;
        $n = random_int(0, $max);
        return str_pad((string)$n, $length, '0', STR_PAD_LEFT);
    }

    public static function alnum(int $length = 16): string
    {
        $length = max(1, $length);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }
        return $out;
    }
}

