<div id="aiet_popup">
	<button id="aiet_closeButton">&times;</button>
	<div id="aiet_grammarForm">
		<label for="aiet_inputText"><?php echo esc_html__('Enter text:','ai-english-teacher'); ?></label>
		<textarea id="aiet_inputText" name="inputText" rows="7"></textarea>
		<div class="aiet_buttons_wrapper">
			<button type="submit" id="aiet_correct_grammar"><?php echo esc_html__('Correct Grammar','ai-english-teacher'); ?></button>
			<button type="submit" id="aiet_rephrase_sentences"><?php echo esc_html__('Rephrase Sentences','ai-english-teacher'); ?></button>
		</div>
	</div>
	<div id="aiet_output"></div>
</div>
<button id="aiet_myButton" style="display: none;"><img src="<?php echo plugin_dir_url( __FILE__ ); ?>../assets/img/spell-check-solid.svg" alt="Spell Check" width="24" height="24"></button>