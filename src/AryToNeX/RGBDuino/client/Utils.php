<?php

namespace AryToNeX\RGBDuino\client;

/**
 * Class Utils
 * @package AryToNeX\RGBDuino\client
 */
class Utils{

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	public static function dominantColorFromImage(string $url) : array{
		$palette = color\Palette::fromFilename($url);
		$extractor = new color\ColorExtractor($palette);

		return color\ColorUtils::fromIntToRgb($extractor->extract(1)[0]);
	}

	/**
	 * @param string $url
	 * @param int    $colors
	 *
	 * @return array
	 */
	public static function dominantColorArrayFromImage(string $url, int $colors = 5) : array{
		$palette = color\Palette::fromFilename($url);
		$extractor = new color\ColorExtractor($palette);
		$colorsArr = $extractor->extract($colors);
		for($i = 0; $i < count($colorsArr); $i++)
			$colorsArr[$i] = color\ColorUtils::fromIntToRgb($colorsArr[$i]);

		return $colorsArr;
	}

	// Desktop environment utils (Thanks to TDesktop)

	/**
	 * @return string
	 */
	public static function getDesktopEnvironment() : string{
		$xdgCurrentDesktop = strtolower(getenv("XDG_CURRENT_DESKTOP"));
		$list = explode(":", $xdgCurrentDesktop);
		$desktopSession = strtolower(getenv("DESKTOP_SESSION"));
		$kdeSession = getenv("KDE_SESSION_VERSION");
		if(!empty($list)){
			if(in_array("unity", $list)){
				// gnome-fallback sessions set XDG_CURRENT_DESKTOP to Unity
				// DESKTOP_SESSION can be gnome-fallback or gnome-fallback-compiz
				if(strpos($desktopSession, "gnome-fallback") >= 0){
					return "Gnome";
				}

				return "Unity";
			}else if(in_array("xfce", $list)){
				return "XFCE";
			}else if(in_array("pantheon", $list)){
				return "Pantheon";
			}else if(in_array("gnome", $list)){
				if(in_array("ubuntu", $list))
					return "Ubuntu";

				return "Gnome";
			}else if(in_array("kde", $list)){
				if($kdeSession == "5"){
					return "KDE5";
				}

				return "KDE4";
			}
		}

		if($desktopSession !== ""){
			if($desktopSession == "gnome" || $desktopSession == "mate"){
				return "Gnome";
			}else if($desktopSession == "kde4" || $desktopSession == "kde-plasma"){
				return "KDE4";
			}else if($desktopSession == "kde"){
				// This may mean KDE4 on newer systems, so we have to check.
				if($kdeSession !== ""){
					return "KDE4";
				}

				return "KDE3";
			}else if(strpos($desktopSession, "xfce") >= 0 || $desktopSession == "xubuntu"){
				return "XFCE";
			}else if($desktopSession == "awesome"){
				return "Awesome";
			}
		}

		// Fall back on some older environment variables.
		// Useful particularly in the DESKTOP_SESSION=default case.
		if(getenv("GNOME_DESKTOP_SESSION_ID") !== ""){
			return "Gnome";
		}else if(getenv("KDE_FULL_SESSION") !== ""){
			if($kdeSession !== ""){
				return "KDE4";
			}

			return "KDE3";
		}

		return "Other";
	}

	// Wallpaper utils

	/**
	 * @param  string $env
	 *
	 * @return string
	 */
	public static function getWallpaperURL(string $env) : string{
		switch($env){
			default:
				echo "Unsupported DE $env!";

				return "";
			case "XFCE":
				return exec("xfconf-query -c xfce4-desktop -p /backdrop/screen0/monitor0/workspace0/last-image");
			case "Gnome":
			case "Unity":
			case "Ubuntu":
			case "Pantheon":
				return exec("gsettings get org.gnome.desktop.background picture-uri");
			case "KDE5":
				// command by @LucentW
				return exec(
					"grep Image= /home/$(whoami)/.config/plasma-org.kde.plasma.desktop-appletsrc | sed 's/Image=file:\/\///'"
				);
		}
	}

}