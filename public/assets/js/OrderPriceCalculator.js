class OrderPriceCalculator {
    constructor(minimumPeople, basePrice) {
        this.minimumPeople = Number(minimumPeople);
        this.basePrice = Number(basePrice);
    }

    calculate(numberOfPeople) {
        const people = Number(numberOfPeople);

        if (
            !Number.isFinite(people) ||
            people < this.minimumPeople ||
            this.minimumPeople <= 0 ||
            this.basePrice < 0
        ) {
            return null;
        }

        const grossPrice = (this.basePrice / this.minimumPeople) * people;
        const hasDiscount = people >= this.minimumPeople + 5;

        return {
            grossPrice,
            discountRate: hasDiscount ? 10 : 0,
            menuPrice: hasDiscount ? grossPrice * 0.90 : grossPrice
        };
    }
}

window.OrderPriceCalculator = OrderPriceCalculator;
