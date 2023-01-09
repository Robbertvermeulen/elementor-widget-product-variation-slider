const { render, useState, useEffect } = wp.element;
import axios from "axios";
import qs from "qs";

const ProductVariationSlider = () => {
  const widget = window.productVariationSlider;
  const productVariations = widget.variations;
  const valueSuffix =
    (widget.valueSuffix && widget.valueSuffix.toLowerCase()) || "";
  const cartButtonText = widget.cartButtonText || "Add to cart";
  const [minRange, setMinRange] = useState(0);
  const [maxRange, setMaxRange] = useState(0);
  const [selectedValue, setSelectedValue] = useState(minRange);
  const [selectedId, setSelectedId] = useState();
  const [price, setPrice] = useState(0);
  const [loading, setLoading] = useState(false);
  const [succeeded, setSucceeded] = useState(false);

  useEffect(() => {
    const getSliderRange = () => {
      let min = 0;
      let max = 0;
      productVariations.forEach((variation) => {
        const attributeValue = variation.attribute_value;
        const range = attributeValue.match(/\d+/g);
        const resultMinRange = parseInt(range[0]);
        const resultMaxRange = parseInt(range[1]);

        if (resultMinRange < min || min === 0) {
          min = resultMinRange;
        }
        if (resultMaxRange > max) {
          max = resultMaxRange;
        }
      });
      return { min, max };
    };
    const { min, max } = getSliderRange();
    setMinRange(min);
    setMaxRange(max);
  }, [productVariations]);

  useEffect(() => {
    setSelectedValue(minRange);
  }, [minRange]);

  useEffect(() => {
    const id = getSelectedVariationId(selectedValue);
    setSelectedId(id);
  }, [selectedValue]);

  useEffect(() => {
    const variation = getVariationById(selectedId);
    if (variation) {
      setPrice(variation.price);
    }
  }, [selectedId]);

  const getSelectedVariationId = (value) => {
    const result = productVariations.find((variation) => {
      const attributeValue = variation.attribute_value;
      const range = attributeValue.match(/\d+/g);
      const resultMinRange = parseInt(range[0]);
      const resultMaxRange = parseInt(range[1]);
      return value >= resultMinRange && value <= resultMaxRange;
    });
    return result?.id;
  };

  const getVariationById = (id) => {
    const result = productVariations.find((variation) => {
      return variation.id === id;
    });
    return result;
  };

  const handleChange = (e) => {
    const value = e.target.value;
    setSelectedValue(value);
  };

  const handleCartButtonClick = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await axios.post(
        window.pvsw.ajaxUrl,
        qs.stringify({
          action: "pvsw_add_to_cart",
          product_id: selectedId,
        })
      );
      if (response?.data?.success) {
        setLoading(false);
        setSucceeded(true);
        if (response.data.redirect_url) {
          window.location.href = response.data.redirect_url;
        }
      }
    } catch (error) {
      console.error(error);
    }
  };

  const priceFormatter = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: window.pvsw.currency,
  });

  return (
    <div className="flex flex-col items-center w-full">
      <div className="w-full mb-4 flex items-center">
        <span className="mx-2">{minRange}</span>
        <input
          type="range"
          className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          min={minRange}
          max={maxRange}
          value={selectedValue}
          onChange={handleChange}
          disabled={loading}
        />
        <span className="mx-2">{maxRange}</span>
      </div>
      <div className="mb-3 text-xl font-semibold">
        <span>{selectedValue}</span>
        <span> {valueSuffix}</span>
      </div>
      <div className="mb-6 text-lg">{priceFormatter.format(price)}</div>
      <div>
        <button
          className="bg-[#2C70F6] border-0 !rounded-md uppercase shadow-md"
          onClick={handleCartButtonClick}
          disabled={loading}
        >
          {(succeeded && "One moment please..") ||
            (loading && "Loading..") ||
            cartButtonText}
        </button>
      </div>
    </div>
  );
};

render(
  <ProductVariationSlider />,
  document.getElementById("product_variation_slider")
);
