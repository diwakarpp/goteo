<?php

/*
 * Este modelo es para la
 */
namespace Goteo\Library {

	use Goteo\Model\Invest,
        Goteo\Model\Project,
        Goteo\Core\Exception;

    class WallFriends {
		public $project = '';
		public $investors = array();
		public $avatars = array(); //listado de avatars válidos con su multiplicador de tamaño
		public $max_multiplier = 32; //màxim multiplicador de tamanys
		public $w_size = 32; //tamaño (width) de la imagen mínima en pixeles
		public $h_size = 32; //tamaño (height) de la imagen mínima en pixeles
		public $w_padding = 0;
		public $h_padding = 0;
		public $show_title = true; //enseña o no el titulo del widget (publi goteo)
		/**
         *
         * @param   type mixed  $id     Identificador
         * @return  type object         Objeto
         */
        public function __construct ($id, $all_avatars=true, $with_title = true) {
			if($this->project = Project::get($id)) {
				$this->show_title = $with_title;
				$this->investors = $this->project->investors;

				$avatars = array();
				foreach($this->investors as $i) {
					if($i->avatar->id != 1 || $all_avatars)
						$avatars[$i->user] = $i->amount;

				}
				$this->avatars = self::pondera($avatars,$this->max_multiplier);

				//arsort($this->avatars);

				$keys = array_keys( $this->avatars );
				shuffle( $keys );
				$this->avatars = array_merge( array_flip( $keys ) , $this->avatars );
				//print_r($this->project);die;

			}
			else {
				//quizá otro mensaje de error?
                throw new \Goteo\Core\Error('404', Text::html('fatal-error-project'));
			}

        }

        /**
         * Pondera un array amb valor minim 1 i valor maxim ?
         * */
        public static function pondera($array = array(),$max_multiplier = 4) {
			$new = array();
			$min = min($array);
			$max = max($array);

			foreach($array as $i => $n) {
				//minim 1, màxim el que toqui
				$num = $n/$min;
				//apliquem alguna funcio que "comprimeixi" els resultats
				$num = round(sqrt($num));
				if($num > $max_multiplier) $num = $max_multiplier;
				$new[$i] = $num;
			}
			return $new;
		}

		/**
		 * Retorna les imatges i contingut en html
		 *
		 * $num_icons: el numero de icones per fila del widget
		 * */
		public function html_content($num_icons = 19) {
			$ret = array();
			foreach($this->avatars as $user => $mult) {
				$style = '';
				$w = $this->w_size;
				$h = $this->h_size;

				$src = SITE_URL . '/image/1/'."$w/$h";
				if($this->investors[$user]->avatar instanceof \Goteo\Model\Image)
					$src = $this->investors[$user]->avatar->getLink($w,$h, true);



				$img = '<a href="'.SITE_URL.'/user/profile/'.$user.'"><img'.$style.' src="' . $src . '" alt="'.$this->investors[$user]->name.'" title="'.$this->investors[$user]->name.'" /></a>';

				for($i = 0; $i<$mult+1; $i++) {

					$ret[] = $img;
					$total = count($ret);
					//cas que es posicioni a partir de la segona columna
					if($num_icons > 14) {
						//final de 1a fila, 2a columna
						if(in_array($total , array($num_icons + 1, $num_icons * 2 - 12, $num_icons * 3 - 25))) {
							$ret[] = '<div class="c"></div>';
						}
						if(in_array($total, array($num_icons * 5 - 38, $num_icons * 6 - 49, $num_icons * 7 - 60))) {
							$ret[] = '<div class="a"></div>';
						}
						if(in_array($total, array($num_icons * 5 - 36, $num_icons * 6 - 47, $num_icons * 7 - 58))) {
							$ret[] = '<div class="b"></div>';
						}
						if(in_array($total , array($num_icons * 9 - 71,$num_icons * 10 - 84))) {
							$ret[] = '<div class="d"></div>';
						}
					}
					//es posiciona a partir de la primera columna (minim tamany possible)
					else {
						if($total == $num_icons) {
							$ret[] = '<div class="c"></div><div class="c"></div><div class="c"></div>';
						}
						if($total == $num_icons * 2 + 1) {
							$ret[] = '<div class="a"></div>';
						}
						if(in_array($total, array($num_icons * 2 + 3, $num_icons * 2 + 5))) {
							$ret[] = '<div class="b"></div><div class="a"></div>';
						}
						if($total == $num_icons * 2 + 7) {
							$ret[] = '<div class="b"></div>';
						}
						if($total == $num_icons * 3 + 8) {
							$ret[] = '<div class="d"></div><div class="d"></div>';
						}

					}
				}
			}

			//afegim el logo al final de tot
			$final = array();
			$total = count($ret);
			$cols = floor(($total + 3*14 + 3*13 + 2*14)/$num_icons);

			if($num_icons > 14) {
				foreach($ret as $i => $v) {
					if(in_array($i, array($num_icons*($cols-1) - 103,$num_icons*$cols - 107))) {
						$final[] = '<div class="e"></div>';
					}
					$final[] = $v;
				}
			}
			else {
				foreach($ret as $i => $v) {
					if(in_array($i, array($num_icons*($cols-2) - 94,$num_icons*($cols-1) - 98))) {
					//if(in_array($i, array($num_icons*($cols-2) - 94))) {
						$final[] = '<div class="e"></div>';
					}
					$final[] = $v;
				}
			}
			return $final;
		}

		/**
		 * Muestra un div con las imagenes en pantalla.
		 * @param type int	$width
		 * @param type int	$height
		 *
		*/
		public function html($width = 608) {

			//cal que siguin multiples del tamany
			$wsize = $this->w_size + $this->w_padding * 2;
			$hsize = $this->h_size + $this->h_padding * 2;
			//num icones per fila
			$num_icons = floor($width / $wsize);
			//tamany minim
			if($num_icons < 15) $num_icons = 14;
			//amplada efectiva
			$width = $wsize * $num_icons;

			$style = "<style type=\"text/css\">";
            // estatico
			$style .= <<<EOF
div.wof {
    font-size: 12px;
    color: #58595b;
    font-family: "Liberation Sans", Helvetica, "Helvetica Neue", Arial, Geneva, sans-serif;
    background-color: #58595c;
    display: inline-block;
    height:auto;
	padding: 0 22px 22px;
}

div.wof > div.ct {
    position:relative;
    clear:both;
}

div.wof > div.ct > div.a,
div.wof > div.ct > div.b,
div.wof > div.ct > div.c {
    display:inline-block;
}

div.wof > div.ct > div.i {
    overflow:hidden;
    padding: 0;
    margin:0;
    position:absolute;
    background:#fff;
	text-align: center;
}

div.wof > div.ct > div.c > div.c1 {
    float:left;
}

div.wof > div.ct > div.c > div.c2 {
    float:right;
}

div.wof a,
div.wof a:link,
div.wof a:visited,
div.wof a:active,
div.wof a:hover {
    text-decoration:none;
    color:#58595c;
}

div.wof h2 {
    display:block;
    font-size: 14px;
    color:#fff;
    padding:0;
    margin: 0;
}

div.wof h2 a,
div.wof h2 a:link,
div.wof h2 a:visited,
div.wof h2 a:active,
div.wof h2 a:hover {
	width:60%;
    display: block;
    height: 21px;
    overflow: hidden;
    background: #58595c;
    color: #fff;
    padding: 7px 0 0 0;
    float:left;
}
div.wof h2 a.right {
	width:30%;
	text-align:right;
	float:right;
}

div.wof>div.ct>div.i h3 {
    color: #0b4f99;
    font-weight:bold;
    text-align: right;
    padding: 0 15px 0 0;
    margin: 8px 0 0;
}

div.wof>div.ct>div.i h3 a {
    color: #0b4f99;
    font-size: 52px;
}

div.wof>div.ct>div.i h3>img {

}

div.wof>div.ct>div.i p {
    color:#58595c;
    font-size:14px;
    text-align: right;
    padding: 0 15px 0 0;
    margin:0;
}

div.wof>div.ct>div.i.a p a{
	color:#0b4f99;
    text-transform:uppercase;
}

div.wof>div.ct>div.i.b h3 {
	padding-right: 15px;
	text-align: right;
}

div.wof>div.ct>div.i.b h3 a {
    color:#95268D;
	font-size: 52px;
}
div.wof>div.ct>div.i.b h3 a img{
	vertical-align:top;
	padding-top:10px;
}
div.wof>div.ct>div.i.b p {
    color:#0b4f99;
    text-align: right;
}

div.wof>div.ct>div.i.b p a {
    color:#58595c;
}

div.wof>div.ct>div.i.c h3 {
    color:#1db3b2;
    text-transform:uppercase;
    text-align:left;
    padding:4px 0 4px 4px;
}

div.wof>div.ct>div.i.c h3 a{
    color:#1db3b2;
	font-size: 18px;
}

div.wof>div.ct>div.i.c p {
    color:#58595c;
    text-align:left;
    padding:0 0 0 4px;
}

div.wof>div.ct>div.i.c>div.c1 p {
    padding:10px;
    font-size:10px;
    line-height:10px;
    text-align: left;
}

div.wof>div.ct>div.i.c>div.c1 p img {
    padding:0 0 4px 0;
}

div.wof>div.ct>div.i.c>div.c1 a {
    color:#1db3b2;
	font-size: 11px;
	text-align: left;
}
div.wof>div.ct>div.i.d p {
	display:block;
	float:left;
	width:220px;
	text-align:left;
	font-size:12px;
	padding:20px 0 0 90px;
}
div.wof>div.ct>div.i.d a {
	display:block;
	width:117px;
	height:22px;
	color:#fff;
	font-size:14px;
	padding:6px 0 0 0;
	margin:20px 20px 0 0;
	text-transform:uppercase;
	background:url(/view/css/project/widget/wof_sup_btn.png) no-repeat;
	overflow:hidden;
	float:right;
}
EOF;
            // dinamico
			$style .= "div.wof>div.ct>a>img {border:0;width:{$this->w_size}px;height:{$this->h_size}px;display:inline-block;padding:{$this->h_padding}px {$this->w_padding}px {$this->h_padding}px {$this->w_padding}px}";
			$style .= "div.wof>div.ct>div.a {display:inline-block;width:" . ($wsize * 5) . "px;height:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.b {display:inline-block;width:" . ($wsize * 8) . "px;height:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.c {display:inline-block;width:" . ($wsize * 14) . "px;height:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.d {display:inline-block;width:" . ($wsize * 14) . "px;height:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.e {display:inline-block;width:" . ($wsize * 4) . "px;height:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.i {overflow:hidden;padding:0;margin:0;position:absolute;height:" . ($hsize * 3) . "px;background:#fff;left:" . ($num_icons < 15 ? "0" : $wsize) . "px;top:" . ($hsize * 5) . "px}";
			$style .= "div.wof>div.ct>div.b.i {left:" . ($wsize * ($num_icons <15 ? 6 : 7)) . "px;top:" . ($hsize * 5) . "px}";
			$style .= "div.wof>div.ct>div.c.i {left:" . ($num_icons < 15 ? "0" : $wsize) . "px;top:" . $hsize . "px}";
			$style .= "div.wof>div.ct>div.d.i {left:" . ($num_icons < 15 ? "0" : $wsize) . "px;top:" . ($hsize * 9) . "px;height:" . ($hsize * 2) . "px;background:url(".SITE_URL."/view/css/project/widget/wof_sup_bck.png) no-repeat}";

			$content = $this->html_content($num_icons);
			$cols = floor((count($content)  + 3*13 + 3*11 + 2*13 +2*3) / $num_icons);

			$style .= "div.wof>div.ct>div.e.i {left:" . (($num_icons - 5) * $wsize) . "px;top:" . ($hsize * ($cols-2)) . "px;height:" . ($hsize * 2) . "px;background:#fff url(".SITE_URL."/view/css/project/widget/wof_logo.png) center no-repeat}";
			$style .= "div.wof>div.ct>div.c>div.c1 {float:left;height:" . ($wsize * 3) . "px;width:" . ($wsize * 3) . "px}";
			$style .= "div.wof>div.ct>div.c>div.c2 {float:right;height:" . ($wsize * 3) . "px;width:" . ($wsize * 11) . "px}";
			$style .= "</style>";

			$title = '<h2><a href="'.SITE_URL.'/project/'.$this->project->id.'">'.Text::get('wof-title').'</a><a href="'.SITE_URL.'" class="right">goteo.org</a></h2>';

			//num finançadors
			$info = '<div class="a i"><h3><a href="'.SITE_URL.'/project/'.$this->project->id.'">' . count($this->project->investors) . '</a></h3><p><a href="'.SITE_URL.'/project/'.$this->project->id.'">'.Text::get('project-view-metter-investors').'</a></p></div>';

			//financiacio, data
			$info .= '<div class="b i"><h3><a href="'.SITE_URL.'/project/'.$this->project->id.'">' . number_format($this->project->invested,0,'',','). '<img src="'.SITE_URL.'/view/css/euro/violet/yl.png" alt="&euro;"></a></h3>';
			$info .= '<p><a href="'.SITE_URL.'/project/'.$this->project->id.'">' . Text::get('project-view-metter-days') . " {$this->project->days} " . Text::get('regular-days') .'</a></p></div>';

			//impulsores, nom, desc
			$info .= '<div class="c i">';
			$info .= '<div class="c1"><p><a href="'.SITE_URL.'/user/'.$this->project->owner.'"><img src="'.SITE_URL.'/image/'.$this->project->user->avatar->id.'/56/56/1" alt="'.$this->project->user->name.'" title="'.$this->project->user->name.'"><br />' . Text::get('regular-by') . ' '  . $this->project->user->name . '</a></p></div>';
			$info .= '<div class="c2"><h3><a href="'.SITE_URL.'/project/'.$this->project->id.'">' . $this->project->name . '</a></h3><p><a href="'.SITE_URL.'/project/'.$this->project->id.'">'.$this->project->subtitle.'</a></p></div>';
			$info .= '</div>';

			//apoyar el proyecto
			$info .= '<div class="d i">';
			$info .= '<p>'.Text::get('wof-join-group').'</p>';
			$info .= '<a href="'.SITE_URL.'/project/'.$this->project->id.'/invest">'.Text::get('wof-support').'</a>';
			$info .= '</div>';

			//logo
			$info .= '<div class="e i">';
			$info .= '</div>';

			return $style . '<div class="wof" style="width:'.$width.'px;">' . ($this->show_title ? $title : '') . '<div class="ct">' . $info . implode("",$content).'</div></div>';
		}
    }
}
