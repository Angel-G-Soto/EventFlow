<x-layouts.header.public>
    <!-- items/index.blade.php -->
    <select id="category-filter">
        <option value="">Select Category</option>
        @foreach($array as $category)
            <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
        @endforeach
    </select>

    <div id="item-list">
        <!-- Filtered items will be loaded here -->
    </div>
</x-layouts.header.public>
<script>
    $(document).ready(function() {
        $('#category-filter').on('change', function() {
            var categoryId = $(this).val();

            $.ajax({
                url: '{{ route("items.filter") }}',
                type: 'GET',
                data: { category_id: categoryId },
                success: function(data) {
                    // Update the item list with the filtered data
                    var itemListHtml = '';
                    $.each(data, function(index, item) {
                        itemListHtml += '<p>' + item.name + '</p>';
                    });
                    $('#item-list').html(itemListHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Error filtering items:", error);
                }
            });
        });
    });
</script>
