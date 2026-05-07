<template>
    <div class="p-4 grid gap-4">
        <!-- Cash In / Cash Out toggle -->
        <div class="flex rounded-xl overflow-hidden border border-[#E5E5EA]">
            <button
                type="button"
                class="flex-1 py-3 text-[15px] font-semibold transition-colors"
                :class="form.type === 'income'
                    ? 'bg-[#34C759] text-white'
                    : 'bg-white dark:bg-gray-900 text-[#8E8E93]'"
                @click="form.type = 'income'"
            >
                <span class="flex items-center justify-center gap-1.5">
                    <i class="icon-add text-lg"></i>
                    {{ $t('pos.cashier.cash_movement.income') }}
                </span>
            </button>
            <button
                type="button"
                class="flex-1 py-3 text-[15px] font-semibold transition-colors border-l border-[#E5E5EA]"
                :class="form.type === 'expense'
                    ? 'bg-[#FF3B30] text-white'
                    : 'bg-white dark:bg-gray-900 text-[#8E8E93]'"
                @click="form.type = 'expense'"
            >
                <span class="flex items-center justify-center gap-1.5">
                    <i class="icon-minus text-lg"></i>
                    {{ $t('pos.cashier.cash_movement.expense') }}
                </span>
            </button>
        </div>

        <!-- Form card -->
        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm p-4 grid gap-3">
            <!-- Category chips -->
            <div>
                <label class="block text-[12px] text-[#8E8E93] mb-1.5">{{ $t('pos.cashier.cash_movement.category') }}</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        type="button"
                        class="px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors"
                        :class="form.category === cat
                            ? 'bg-[#00B8D9] text-white border-[#00B8D9]'
                            : 'border-[#E5E5EA] text-[#3C3C43] bg-white dark:bg-gray-800 dark:text-gray-300'"
                        @click="form.category = cat"
                    >{{ $t('pos.cashier.cash_movement.categories.' + cat) }}</button>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-[12px] text-[#8E8E93] mb-1">{{ $t('pos.cashier.cash_movement.description') }}</label>
                <input
                    v-model="form.description"
                    type="text"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-2.5 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white"
                    :placeholder="$t('pos.cashier.cash_movement.description')"
                />
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-[12px] text-[#8E8E93] mb-1">{{ $t('pos.cashier.cash_movement.amount') }}</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#8E8E93] text-[15px] font-medium">¥</span>
                    <input
                        v-model.number="form.amount"
                        type="number"
                        inputmode="decimal"
                        min="0.01"
                        step="0.01"
                        class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] pl-7 pr-3 py-2.5 text-[20px] font-semibold text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white text-right"
                        placeholder="0"
                    />
                </div>
            </div>

            <p v-if="formError"   class="text-[#FF3B30] text-[13px]">{{ formError }}</p>
            <p v-if="successMsg"  class="text-[#34C759] text-[13px] font-semibold">{{ successMsg }}</p>

            <button
                type="button"
                class="w-full rounded-xl py-3 text-[15px] font-semibold text-white disabled:opacity-50 active:opacity-80 transition-colors"
                :class="form.type === 'income' ? 'bg-[#34C759]' : 'bg-[#FF3B30]'"
                :disabled="submitting || form.amount <= 0"
                @click="submit"
            >
                <span v-if="submitting" class="icon-spinner animate-spin mr-1"></span>
                {{ submitting ? '…' : $t('pos.cashier.cash_movement.submit') }}
            </button>
        </div>

        <!-- Recent movements -->
        <div v-if="recentMovements.length" class="rounded-xl bg-white dark:bg-gray-900 shadow-sm p-4">
            <h3 class="text-[13px] font-semibold text-[#8E8E93] mb-2 uppercase tracking-wide">最近の記録</h3>
            <ul class="divide-y divide-[#F2F2F7]">
                <li v-for="m in recentMovements" :key="m.id" class="flex items-center justify-between py-2.5 gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-[14px] text-[#1C1C1E] truncate">{{ m.description || $t('pos.cashier.cash_movement.categories.' + m.category) }}</p>
                        <p class="text-[11px] text-[#8E8E93]">{{ m.movementDate }}</p>
                    </div>
                    <span
                        class="text-[15px] font-bold flex-shrink-0"
                        :class="m.type === 'income' ? 'text-[#34C759]' : 'text-[#FF3B30]'"
                    >{{ m.type === 'income' ? '+' : '-' }}¥{{ m.amount }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useMutation } from '@vue/apollo-composable';
import { RECORD_CASH_MOVEMENT } from '@graphql/cashMovements.js';

const categories = ['rent', 'electricity', 'gas', 'salary', 'supplies', 'misc', 'sales_other'];

const form = ref({
    type: 'expense',
    category: 'misc',
    description: '',
    amount: 0,
});
const submitting      = ref(false);
const formError       = ref('');
const successMsg      = ref('');
const recentMovements = ref([]);

const { mutate: recordCashMovement } = useMutation(RECORD_CASH_MOVEMENT);

async function submit() {
    formError.value  = '';
    successMsg.value = '';

    if (!form.value.description || form.value.amount <= 0) {
        formError.value = '説明と金額は必須です。';
        return;
    }

    submitting.value = true;

    try {
        const { data } = await recordCashMovement({ input: { ...form.value } });

        if (data?.recordCashMovement?.success) {
            const m = data.recordCashMovement.movement;
            if (m) {
                recentMovements.value.unshift(m);
                if (recentMovements.value.length > 10) recentMovements.value.pop();
            }
            successMsg.value = data.recordCashMovement.message ?? '保存しました';
            form.value = { type: form.value.type, category: 'misc', description: '', amount: 0 };
            setTimeout(() => { successMsg.value = ''; }, 3000);
        } else {
            formError.value = data?.recordCashMovement?.errors ?? 'エラーが発生しました';
        }
    } catch (e) {
        formError.value = e.message;
    } finally {
        submitting.value = false;
    }
}
</script>
