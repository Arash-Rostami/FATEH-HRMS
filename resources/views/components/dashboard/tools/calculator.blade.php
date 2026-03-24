<div
    x-data="calculator()"
    x-init="init()"
    x-ref="calculatorModal"
    class="fixed top-auto z-10 inset-0 hidden overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity justify-center">

    <div class="flex items-center justify-center min-h-screen p-4 w-2/3 md:w-1/4 max-h-screen">
        <div
            class="relative bg-[var(--md-sys-color-primary)] rounded-xl shadow-xl transform transition-all sm:max-w-md w-full">
            <div class="p-6">
                <input type="text"
                       id="display"
                       class="w-full mb-4 border border-gray-300 rounded-xl px-3 py-2 text-2xl text-black text-right cursor-copy"
                       x-model="formattedDisplay"
                       x-ref="display"
                       @click="copyToClipboard()"
                       readonly>
                <div class="grid grid-cols-4 gap-2">
                    <?php
                    $buttons = [
                        ['value' => '7'], ['value' => '8'], ['value' => '9'], ['value' => '/', 'symbol' => '➗'],
                        ['value' => '4'], ['value' => '5'], ['value' => '6'], ['value' => '*', 'symbol' => '✖️'],
                        ['value' => '1'], ['value' => '2'], ['value' => '3'], ['value' => '+', 'symbol' => '➕'],
                        ['value' => 'C', 'action' => 'clearDisplay()'], ['value' => '.'], ['value' => '0'], ['value' => '-', 'symbol' => '➖'],
                        ['value' => 'Close', 'action' => 'closeModal()'], ['value' => '=', 'action' => 'calculate()']
                    ];
                    ?>
                    @foreach ($buttons as $button)
                        <button @click="{{  $button['action'] ?? 'appendToDisplay(\'' . $button['value'] . '\')' }}"
                            @class([
                                "bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl px-3 py-2 text-xl font-bold min-w-[3rem] min-h-[3rem]']" => !isset($button['symbol']) && !isset($button['action']),
                                "bg-orange-500 hover:bg-orange-700 rounded-xl px-4 py-3 text-lg min-w-[3rem]" => isset($button['symbol']) ,
                                "bg-red-500 hover:bg-red-700 text-white rounded-xl px-3 py-2 text-xl font-bold min-w-[3rem] min-h-[3rem]"=> isset($button['action']) && $button['value'] === 'C',
                                "bg-[var(--md-sys-color-primary-container)] mt-2 text-gray-700 dark:text-gray-200 py-4 px-3 min-w-[3rem] min-h-[3rem] rounded-xl"=> isset($button['action']) && $button['value'] === 'Close',
                                "bg-green-500 hover:bg-green-700 text-white rounded-xl px-3 py-2 text-xl font-bold min-w-[3rem] min-h-[3rem]"=> isset($button['action']) && $button['value'] === '=',
                                 ])>
                            @if (isset($button['symbol']))
                                <span class="filter grayscale">{{ $button['symbol'] }}</span>
                            @else
                                {{ $button['value'] }}
                            @endif
                        </button>
                        @if($button['value'] === 'Close')
                            {!! str_repeat('<span></span>' , 2) !!}
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
