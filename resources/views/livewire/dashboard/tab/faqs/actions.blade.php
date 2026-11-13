<div class="flex items-center gap-0.5 shrink-0" @click.stop>
    <x-ui.buttons.copy :text="$presenter->copyText($faq)" message="متن پرسش و پاسخ کپی شد" class="!p-1.5" title="کپی متن"/>
    <x-ui.buttons.bookmark :id="$faq->id" scope="faq"/>
</div>
