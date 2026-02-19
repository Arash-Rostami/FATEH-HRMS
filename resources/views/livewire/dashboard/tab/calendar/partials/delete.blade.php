<x-dashboard.modal.confirm
    wire:model="isDeleteModalOpen"
    title="حذف رویداد؟"
    message="آیا مطمئن هستید که می‌خواهید این رویداد را حذف کنید؟ این عملیات غیرقابل بازگشت است."
    action="deleteEvent"
    confirm-text="بله، حذف کن"
    cancel-text="لغو"
/>
