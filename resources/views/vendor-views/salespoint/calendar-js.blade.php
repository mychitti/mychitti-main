  <script>
      window.currentRoute = "{{ Route::currentRouteName() }}";
      console.log(window.currentRoute);
      const sampleData = @json($sales);
      let currentView = 'calendar';
      let today = new Date();
      let currentYear = today.getFullYear();
      let currentMonth = today.getMonth() + 1; // 7 +1  (8 for august)

      const monthNames = [
          '', 'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
      ];

      const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

      function setView(view) {
          let baseUrl = "{{ route('vendor.pos.calendar-export') }}";

          currentView = view;
          document.querySelectorAll('.btn').forEach(btn => btn.classList.remove('active'));
          event.target.classList.add('active');

          if (view === 'calendar') {
              document.getElementById('calendarView').style.display = 'grid';
              document.getElementById('yearView').style.display = 'none';
              //  document.getElementById('monthSummary').style.display = 'block';
              $(".text_span").text(`${monthNames[currentMonth]} ${currentYear}`);
              $(".export_calendar_btn").attr(
                  "href",
                  `${baseUrl}?month=${currentMonth}&year=${currentYear}`
              );
              generateCalendar();
          } else {
              document.getElementById('calendarView').style.display = 'none';
              document.getElementById('yearView').style.display = 'grid';
              // document.getElementById('monthSummary').style.display = 'none';
              $(".text_span").text(` ${currentYear}`);
              $(".export_calendar_btn").attr(
                  "href",
                  `${baseUrl}?year=${currentYear}`
              );
              generateYearView();
          }
      }

      function navigate(direction) {
          if (currentView === 'calendar') {
              currentMonth += direction;
              if (currentMonth > 12) {
                  currentMonth = 1;
                  currentYear++;
              } else if (currentMonth < 1) {
                  currentMonth = 12;
                  currentYear--;
              }
              generateCalendar();
          } else {
              currentYear += direction;
              generateYearView();
          }
          updatePeriodDisplay();
      }

      function updatePeriodDisplay() {
          const periodElement = document.getElementById('currentPeriod');
          if (currentView === 'calendar') {
              periodElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
          } else {
              periodElement.textContent = `${currentYear}`;
          }
      }

      function generateCalendar() {
          const calendarElement = document.getElementById('calendarView');
          calendarElement.innerHTML = '';
          $(".text_span").text(`${monthNames[currentMonth]} ${currentYear}`);
          let baseUrl = "{{ route('vendor.pos.calendar-export') }}";
          $(".export_calendar_btn").attr(
              "href",
              `${baseUrl}?month=${currentMonth}&year=${currentYear}`
          );

          // Add day headers
          dayNames.forEach(day => {
              const header = document.createElement('div');
              header.className = 'calendar-header';
              header.textContent = day;
              calendarElement.appendChild(header);
          });

          // Get first day of month and number of days
          const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
          const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
          const daysInPrevMonth = new Date(currentYear, currentMonth - 1, 0).getDate();

          // Add previous month's trailing days
          for (let i = firstDay - 1; i >= 0; i--) {
              const day = daysInPrevMonth - i;
              const dayElement = createDayElement(day, true);
              calendarElement.appendChild(dayElement);
          }

          // Add current month's days
          for (let day = 1; day <= daysInMonth; day++) {
              const dayElement = createDayElement(day, false);
              calendarElement.appendChild(dayElement);
          }

          // Add next month's leading days
          const totalCells = calendarElement.children.length - 7; // Subtract headers
          const remainingCells = 42 - totalCells; // 6 rows × 7 days
          for (let day = 1; day <= remainingCells; day++) {
              const dayElement = createDayElement(day, true);
              calendarElement.appendChild(dayElement);
          }

          updateMonthlySummary();
      }

      function createDayElement(day, isOtherMonth) {
          var baseUrl =
              "{{ request()->routeIs('vendor.inventory.dashboard') || request()->routeIs('vendor.inventory.sale.orders') ? route('vendor.inventory.report.sale') : route('vendor.pos.report') }}";
          var path = 'date_range=custom&custom_date_range=' + currentYear + '-' + currentMonth + '-' + day + '+-+' +
              currentYear + '-' + currentMonth + '-' + day;
          const dayElement = document.createElement('a');
          dayElement.className = 'calendar-day';
          dayElement.setAttribute('href', baseUrl + '?' + path);

          if (isOtherMonth) {
              dayElement.classList.add('other-month');
          }

          // Check if it's today
          const today = new Date();
          if (!isOtherMonth &&
              currentYear === today.getFullYear() &&
              currentMonth === today.getMonth() + 1 &&
              day === today.getDate()) {
              dayElement.classList.add('today');
          }

          const dayNumber = document.createElement('div');
          dayNumber.className = 'day-number';
          dayNumber.textContent = day;
          dayElement.appendChild(dayNumber);

          // Add sales data if available
          if (!isOtherMonth && sampleData[currentYear] && sampleData[currentYear][currentMonth] && sampleData[currentYear]
              [currentMonth][day]) {
              const data = sampleData[currentYear][currentMonth][day];
              const dayData = document.createElement('div');
              dayElement.classList.add(data.category); 
              dayData.className = 'day-data';
              if (window.currentRoute.startsWith("vendor.account.") || window.currentRoute.startsWith("admin.account.")) {
                  dayData.innerHTML = `
        <div class="sale-amount credit">Credit: ₹${data.credit.toLocaleString()}</div>
        <div class="sale-amount debit">Debit: ₹${data.debit.toLocaleString()}</div>
    `;
              } else if (window.currentRoute.startsWith("vendor.task.") || window.currentRoute.startsWith("admin.task.")) {
                  dayData.innerHTML = ` 
        <div class="sale-amount">Created : ${data.created_task}</div>
        <div class="sale-amount">Completed : ${data.completed_task}</div>
        <div class="sale-amount">Pending : ${data.pending_task}</div>
        <div class="sale-amount">Cancelled : ${data.cancelled_task}</div>
    `;
              } else {
                  dayData.innerHTML = `
        <div class="sale-amount">₹${(data.amount ?? 0).toLocaleString()}</div>
        <div class="token-count">🎫 ${data.tokens ?? 0}</div>
    `;
              } 
              dayElement.appendChild(dayData);
          }

          dayElement.addEventListener('click', () => {
              if (!isOtherMonth) {
                  document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
                  dayElement.classList.add('selected');
              }
          });

          return dayElement;
      }

      function generateYearView() {
          const yearViewElement = document.getElementById('yearView');
          yearViewElement.innerHTML = '';

          for (let month = 1; month <= 12; month++) {
              const monthCard = document.createElement('div');
              monthCard.className = 'month-card';

              const monthName = document.createElement('div');
              monthName.className = 'month-name';
              monthName.textContent = monthNames[month];
              monthCard.appendChild(monthName);

              const monthStats = document.createElement('div');
              monthStats.className = 'month-stats';

              // Calculate month totals
              let totalSales = 0;
              let totalTokens = 0;
              if (window.currentRoute.startsWith("vendor.task.") || window.currentRoute.startsWith("admin.task.")) {
                  if (sampleData[currentYear] && sampleData[currentYear][month]) {
                      Object.values(sampleData[currentYear][month]).forEach(day => {

                          totalSales += day.task;
                      });
                  }
                  monthStats.innerHTML = ` 
                    <div>Tasks: ${totalSales}</div>
                `;
              } else if (!window.currentRoute.startsWith("vendor.account.") && !window.currentRoute.startsWith("admin.account.")) {
                  if (sampleData[currentYear] && sampleData[currentYear][month]) {
                      Object.values(sampleData[currentYear][month]).forEach(day => {

                          totalSales += day.amount;
                          totalTokens += day.tokens;
                      });
                  }

                  monthStats.innerHTML = `
                    <div>Sales: ₹${totalSales.toLocaleString()}</div>
                    <div>Tokens: ${totalTokens}</div>
                `;
              } else {
                  if (sampleData[currentYear] && sampleData[currentYear][month]) {
                      Object.values(sampleData[currentYear][month]).forEach(day => {
                          totalSales += day.credit; // Use credit for sales
                          totalTokens += day.debit; // Use debit for tokens
                      });
                  }

                  monthStats.innerHTML = `
                    <div>Credit: ₹${totalSales.toLocaleString()}</div>
                    <div>Debit: ₹${totalTokens.toLocaleString()}</div>
                `;
              }
              monthCard.appendChild(monthStats);

              monthCard.addEventListener('click', () => {
                  currentMonth = month;
                  setView('calendar');
                  document.querySelector('.btn-select').classList.add('active');
                  document.querySelector('.btn-select-secondary').classList.remove('active');
              });

              yearViewElement.appendChild(monthCard);
          }
      }

      function updateMonthlySummary() {
          let totalSales = 0;
          let totalTokens = 0;
          let activeDays = 0;

          if (sampleData[currentYear] && sampleData[currentYear][currentMonth]) {
              Object.values(sampleData[currentYear][currentMonth]).forEach(day => {
                  totalSales += day.amount;
                  totalTokens += day.tokens;
                  activeDays++;
              });
          }

          // document.getElementById('totalSales').textContent = `₹${totalSales.toLocaleString()}`;
          //  document.getElementById('totalTokens').textContent = totalTokens.toLocaleString();
          // document.getElementById('avgSale').textContent = activeDays > 0 ? `₹${Math.round(totalSales / activeDays).toLocaleString()}` : '₹0';
      }

      // Initialize the dashboard
      updatePeriodDisplay();
      generateCalendar();
  </script>
