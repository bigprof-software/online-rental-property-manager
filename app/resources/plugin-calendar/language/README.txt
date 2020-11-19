This folder contains language files for translating the pages created by the Calendar plugin.
Those files are JavaScript files named in the format xx.js, where xx is the 2 letter
abbreviation of the language name, as defined at
https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes .. For example, en.js for English, 
de.js for German, ... etc.

Each file contains language keys and translations in the following format:

	AppGini.Calendar.language = AppGini.Calendar.language || {};
	// change 'en' in the next line to the code of the language being defined
	AppGini.Calendar.language.en = {
		'key1': 'Value 1',
		'key2': 'Value 2',
		/* and so on .. */
	}

When defining new language files:
 1. Make a copy of en.js, and rename the copy to the new language 2 letter abbreviation.
 2. Open the renamed file in a text editor.
 3. Change 'en' in line 2 of the file to the 2 letter abbreviation of the new language.
 4. Replace the strings to the right of colons with the translated strings.
    Special placeholders in the string in the format %placeholder% should NOT be translated
    as they will be replaced by some value in the UI.
 5. The new language would now be available for users to select in the calendar pages.
