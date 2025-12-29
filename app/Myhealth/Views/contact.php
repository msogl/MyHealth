<div id="main-container-medium">
    <h1>Contact Us</h1>

    <div class="main-inner">
        <div id="error-msg" class="error"></div>
        <div id="contact">
            <p>
                <label>Subject</label><br />
                <input type="text" id="subject" class="w-full" value="<?=_WValue(Request('subject'))?>" maxlength="76">
            </p>

            <p>
                <label>Message</label><br />
                <textarea id="message" value="" class="w-full" rows="10"><?=_W(Request('message'))?></textarea>
            </p>

            <p class="submit">
                <button type="button" id="cancel-btn" class="button">Cancel</button>
                <button type="button" id="send-btn" class="button">Send</button>
            </p>
        </form>
    </div>
</div>

<script src="<?=_asset('js/views/Contact.js')?>"></script>