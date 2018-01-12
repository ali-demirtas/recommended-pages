<?php

class recommendedPages extends Plugin {

	public function init()
	{
		// Fields and default values for the database of this plugin
		$this->dbFields = array(
			'label'=>'Recommended Pages',
			'amountOfItems'=>5
		);
	}

	/**
	 * Skip common words innodb filter https://mariadb.com/kb/en/library/full-text-index-stopwords/
	 */
	protected $blacklistWords = ['a', 'as', 'com', 'from', 'is', 'on', 'this', 'when', 'with', 'about', 'at', 'de', 'how', 'it', 'or', 'to', 'where', 'und', 'an', 'be', 'en', 'i', 'la', 'that', 'was', 'who', 'the', 'are', 'by', 'for', 'in', 'of', 'the', 'what', 'will', 'www'];

	/**
	 * Replace -, / to spaces and remove blacklisted words
	 * @param  string $text [slug]
	 * @return [string]     [simplified slug]
	 */
	protected function removeUnnecessaryCharacters(string $text)
	{
	    $tmp = str_replace(['-', '/'], ' ', $text);
	    $tmp = explode(' ', $tmp);
	    $tmp = implode(array_diff($tmp, $this->blacklistWords), ' ');
	    return $tmp;
	}

	/**
	 * [Finds recommended pages based on the search string (usually slug)]
	 * @param  [type] $searchString [Current page slug / search query]
	 * @return [type] array         [Recommended pages results]
	 */
	protected function findRecommendedPages(string $searchString)
	{

		global $dbPages;

		$query = $this->removeUnnecessaryCharacters($searchString);

		// Page number the first one
		$pageNumber = 1;

		// get all pages
		$amountOfItems = -1;

		// Only published pages
		$onlyPublished = true;

		// Get the list of pages
		$pages = $dbPages->getList($pageNumber, $amountOfItems, $onlyPublished, true);

		$results = [];

		foreach ($pages as $pageKey) {

		    // Skip if the current page is found.
		    if ($searchString === $pageKey) {
		        continue;
		    }

		    $pg = $this->removeUnnecessaryCharacters($pageKey);

		    similar_text($query, $pg, $percent);
		    if ($percent > 0) {
		        $results[] = [
		            'page' => $pageKey,
		            'score' => $percent
		        ];
		    }
		}
		/**
		 * Sort the results from high to low score
		 */
	    usort($results, function($a, $b) { return $b['score'] > $a['score'] ;});
	    return $results;
	}

	// Method called on the settings of the plugin on the admin area
	public function form()
	{
		global $Language;

		$html  = '<div>';
		$html .= '<label>'.$Language->get('Label').'</label>';
		$html .= '<input id="jslabel" name="label" type="text" value="'.$this->getValue('label').'">';
		$html .= '<span class="tip">'.$Language->get('This title is almost always used in the sidebar of the site').'</span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$Language->get('Amount of items').'</label>';
		$html .= '<input id="jsamountOfItems" name="amountOfItems" type="text" value="'.$this->getValue('amountOfItems').'">';
		$html .= '</div>';

		return $html;
	}

	// Method called on the sidebar of the website
	public function siteSidebar()
	{
		global $Language;
		global $Url;
		global $Site;
		global $dbPages;
		// global $pagesByParent;

		// Amount of pages to show
		$amountOfItems = $this->getValue('amountOfItems');

		/**
		 * Only display on individual pages
		 */
		if ($Url->whereAmI() === 'page') {

			$currentPage = $Url->slug();

			$results = $this->findRecommendedPages($currentPage);

			/**
			 * Display only if results are found
			 */
			if (!empty($results)) {
				// HTML for sidebar
				$html  = '<div class="plugin plugin-pages">';
				$html .= '<h2 class="plugin-label">'.$this->getValue('label').'</h2>';
				$html .= '<div class="plugin-content">';
				$html .= '<ul>';

				// Display results
				$count = 0;
				foreach ($results as $result) {
					/**
					 * Allow only no. of pages set in config
					 */
					if ($count >= $amountOfItems ) { break; }

					// Create the page object from the page key
					$page = buildPage($result['page']);
					$html .= '<li>';
					$html .= '<a href="'.$page->permalink().'">';
					$html .= $page->title();
					$html .= '</a>';
					$html .= '</li>';
					$count++;
				}
				$html .= '</ul>';
		 		$html .= '</div>';
		 		$html .= '</div>';
				return $html;
			}
		}
	}
}
