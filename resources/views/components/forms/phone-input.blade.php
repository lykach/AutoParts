@once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('phoneInput', () => ({
                phone: '{{ old($name, $value) }}',
                
                formatPhone() {
                    // Видаляємо всі символи крім цифр
                    let numbers = this.phone.replace(/\D/g, '');
                    
                    // Якщо починається з 0, додаємо 38
                    if (numbers.startsWith('0')) {
                        numbers = '38' + numbers;
                    }
                    
                    // Якщо не починається з 38, додаємо
                    if (!numbers.startsWith('38')) {
                        numbers = '38' + numbers;
                    }
                    
                    // Обрізаємо до 12 цифр (38 + 0 + 9 цифр)
                    numbers = numbers.substring(0, 12);
                    
                    // Форматуємо: +38 (0XX) XXX-XX-XX
                    let formatted = '';
                    if (numbers.length > 0) {
                        formatted = '+' + numbers.substring(0, 2);
                    }
                    if (numbers.length > 2) {
                        formatted += ' (0' + numbers.substring(3, 5);
                    }
                    if (numbers.length > 4) {
                        formatted += ') ' + numbers.substring(5, 8);
                    }
                    if (numbers.length > 7) {
                        formatted += '-' + numbers.substring(8, 10);
                    }
                    if (numbers.length > 9) {
                        formatted += '-' + numbers.substring(10, 12);
                    }
                    
                    this.phone = formatted;
                }
            }));
        });
    </script>
    @endpush
@endonce
